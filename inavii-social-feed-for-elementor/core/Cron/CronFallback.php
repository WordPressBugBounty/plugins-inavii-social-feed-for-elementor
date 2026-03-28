<?php
declare(strict_types=1);

namespace Inavii\Instagram\Cron;

use Inavii\Instagram\Account\Cron\AccountStatsCron;
use Inavii\Instagram\Account\Cron\AccountTokenCron;
use Inavii\Instagram\Media\Cron\MediaSyncCron;
use Inavii\Instagram\RestApi\PublicRequestKey;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Fallback runner for sync tasks when WP-Cron is not firing reliably.
 */
final class CronFallback {

	private const CHECK_TRANSIENT       = 'inavii_media_sync_check';
	private const DEFAULT_PING_INTERVAL = 7200; // 2 hours
	private const DIRECT_LIMIT          = 5;

	private Scheduler $scheduler;
	private MediaSyncCron $cron;
	private AccountStatsCron $accountStatsCron;
	private AccountTokenCron $accountTokenCron;
	private PublicRequestKey $publicKeys;

	public function __construct(
		Scheduler $scheduler,
		MediaSyncCron $cron,
		AccountStatsCron $accountStatsCron,
		AccountTokenCron $accountTokenCron,
		PublicRequestKey $publicKeys
	) {
		$this->scheduler        = $scheduler;
		$this->cron             = $cron;
		$this->accountStatsCron = $accountStatsCron;
		$this->accountTokenCron = $accountTokenCron;
		$this->publicKeys       = $publicKeys;
	}

	public function register(): void {
		add_action( 'admin_init', [ $this, 'maybeRunFromAdmin' ] );
		add_action( 'wp_footer', [ $this, 'renderPingScript' ], 20 );
	}

	/**
	 * Admin-side fallback (server-side).
	 */
	public function maybeRunFromAdmin(): void {
		$this->checkAndTrigger();
	}

	/**
	 * Frontend fallback (JS ping).
	 */
	public function renderPingScript(): void {
		if ( is_admin() ) {
			return;
		}

		if ( ! $this->shouldPing() ) {
			return;
		}

		$url     = rest_url( 'inavii/v2/cron/ping' );
		$payload = wp_json_encode(
			[
				'url'   => $url,
				'key'   => $this->publicKeys->createCronPingKey(),
			]
		);

		$script = "(function(){try{var data={$payload};if(!data||!data.url||!data.key){return;}var opts={method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-Inavii-Cron-Key':data.key}};fetch(data.url,opts).catch(function(){});}catch(e){}})();";
		wp_print_inline_script_tag( $script, [ 'id' => 'inavii-media-sync-ping' ] );
	}

	public function handlePing( WP_REST_Request $request ): WP_REST_Response {
		$triggered = $this->checkAndTrigger();

		return new WP_REST_Response(
			[
				'triggered' => $triggered,
				'overdue'   => $this->isAnyOverdue(),
			]
		);
	}

	private function checkAndTrigger(): bool {
		if ( ! $this->isPingEnabled() ) {
			return false;
		}

		if ( $this->isThrottled() ) {
			return false;
		}

		$overdueMedia  = $this->isMediaOverdue();
		$overdueStats  = $this->isStatsOverdue();
		$overdueTokens = $this->isTokenOverdue();
		$this->markChecked();

		if ( ! $overdueMedia && ! $overdueStats && ! $overdueTokens ) {
			return false;
		}

		if ( $overdueMedia ) {
			$this->ensureMediaScheduled();
			if ( $this->shouldRunDirectly() ) {
				$this->cron->runWithLimit( self::DIRECT_LIMIT );
			} else {
				if ( ! $this->spawnCron() ) {
					$this->runDirectFallback();
				}
			}
		}

		if ( $overdueStats ) {
			$this->ensureAccountStatsScheduled();
			if ( $this->shouldRunDirectly() ) {
				$this->accountStatsCron->run();
			} else {
				if ( ! $this->spawnCron() ) {
					$this->runDirectFallback();
				}
			}
		}

		if ( $overdueTokens ) {
			$this->ensureAccountTokensScheduled();
			if ( $this->shouldRunDirectly() ) {
				$this->accountTokenCron->run();
			} else {
				if ( ! $this->spawnCron() ) {
					$this->runDirectFallback();
				}
			}
		}

		return true;
	}

	private function shouldPing(): bool {
		if ( ! $this->isPingEnabled() ) {
			return false;
		}

		if ( $this->isThrottled() ) {
			return false;
		}

		return $this->isAnyOverdue();
	}

	private function isPingEnabled(): bool {
		$enabled = apply_filters( 'inavii/social-feed/media/sync/ping_enabled', true );

		return (bool) $enabled;
	}

	private function isThrottled(): bool {
		return get_transient( self::CHECK_TRANSIENT ) !== false;
	}

	private function markChecked(): void {
		set_transient( self::CHECK_TRANSIENT, 1, $this->pingInterval() );
	}

	private function pingInterval(): int {
		$ttl = (int) apply_filters( 'inavii/social-feed/media/sync/ping_interval', self::DEFAULT_PING_INTERVAL );

		if ( $ttl <= 0 ) {
			return self::DEFAULT_PING_INTERVAL;
		}

		return $ttl;
	}

	private function isAnyOverdue(): bool {
		return $this->isMediaOverdue() || $this->isStatsOverdue() || $this->isTokenOverdue();
	}

	private function isMediaOverdue(): bool {
		$now          = time();
		$overdueAfter = $this->pingInterval();

		$next = wp_next_scheduled( MediaSyncCron::HOOK );
		if ( $next === false ) {
			return true;
		}

		if ( $next + $overdueAfter < $now ) {
			return true;
		}

		$lastRun = (int) get_option( MediaSyncCron::LAST_RUN_OPTION, 0 );
		if ( $lastRun > 0 && $lastRun + $overdueAfter < $now ) {
			return true;
		}

		return false;
	}

	private function isStatsOverdue(): bool {
		$now          = time();
		$overdueAfter = $this->pingInterval();

		$next = wp_next_scheduled( AccountStatsCron::HOOK );
		if ( $next === false ) {
			return true;
		}

		if ( $next + $overdueAfter < $now ) {
			return true;
		}

		$lastRun = (int) get_option( AccountStatsCron::LAST_RUN_OPTION, 0 );
		if ( $lastRun > 0 && $lastRun + $overdueAfter < $now ) {
			return true;
		}

		return false;
	}

	private function isTokenOverdue(): bool {
		$now          = time();
		$overdueAfter = $this->tokenOverdueInterval();

		$next = wp_next_scheduled( AccountTokenCron::HOOK );
		if ( $next === false ) {
			return true;
		}

		if ( $next + $overdueAfter < $now ) {
			return true;
		}

		$lastRun = (int) get_option( AccountTokenCron::LAST_RUN_OPTION, 0 );
		if ( $lastRun > 0 && $lastRun + $overdueAfter < $now ) {
			return true;
		}

		return false;
	}

	private function ensureMediaScheduled(): void {
		$this->scheduler->scheduleIfMissing( MediaSyncCron::HOOK, Scheduler::INTERVAL_1_HOUR );
	}

	private function ensureAccountStatsScheduled(): void {
		$this->scheduler->scheduleIfMissing( AccountStatsCron::HOOK, Scheduler::INTERVAL_1_HOUR );
	}

	private function ensureAccountTokensScheduled(): void {
		$this->scheduler->scheduleIfMissing( AccountTokenCron::HOOK, Scheduler::INTERVAL_1_WEEK );
	}

	private function shouldRunDirectly(): bool {
		if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
			return false;
		}

		return $this->isDirectFallbackEnabled();
	}

	private function isDirectFallbackEnabled(): bool {
		return (bool) apply_filters( 'inavii/social-feed/media/sync/direct_fallback_enabled', true );
	}

	private function spawnCron(): bool {
		if ( function_exists( 'spawn_cron' ) ) {
			return (bool) spawn_cron( time() );
		}

		$scheduled = wp_schedule_single_event( time() + 60, MediaSyncCron::HOOK );

		return (bool) $scheduled;
	}

	private function runDirectFallback(): void {
		if ( ! $this->isDirectFallbackEnabled() ) {
			return;
		}

		$this->cron->runWithLimit( self::DIRECT_LIMIT );
		$this->accountStatsCron->run();
		$this->accountTokenCron->run();
	}

	private function tokenOverdueInterval(): int {
		$default = 7 * DAY_IN_SECONDS;
		$value   = (int) apply_filters( 'inavii/social-feed/account/token/refresh_overdue', $default );

		return $value > 0 ? $value : $default;
	}
}
