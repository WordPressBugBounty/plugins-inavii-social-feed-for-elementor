<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config;

use Inavii\Instagram\Cron\Scheduler;
use Inavii\Instagram\Account\Cron\AccountTokenCron;
use Inavii\Instagram\Account\Cron\AccountStatsCron;
use Inavii\Instagram\Media\Cron\MediaQueueCron;
use Inavii\Instagram\Media\Cron\MediaSyncCron;
use Inavii\Instagram\Cron\CronFallback;
use Inavii\Instagram\Media\Cron\MediaSourceCleanupCron;
use Inavii\Instagram\Media\Application\Hooks\AccountHooks;
use Inavii\Instagram\Media\Application\Hooks\FeedHooks;
use Inavii\Instagram\Media\Application\Hooks\SyncHooks;
use Inavii\Instagram\Config\Troubleshooting\HookDiagnostics;
use Inavii\Instagram\Front\Application\Contracts\GlobalFeedRendererRuntime;
use Inavii\Instagram\Front\Application\GlobalReconnectNoticeRenderer;
use Inavii\Instagram\Front\Application\Contracts\GlobalFeedHooksRuntime;
use Inavii\Instagram\FrontIndex\Application\Hooks\FrontIndexHooks;

/**
 * Bootstraps runtime services (non-install concerns).
 */
final class Initializer {
	private const LEGACY_CRON_CLEANUP_OPTION = 'inavii_social_feed_legacy_cron_cleanup_done';

	private Scheduler $scheduler;
	private MediaQueueCron $mediaQueueCron;
	private MediaSyncCron $mediaSyncCron;
	private CronFallback $cronFallback;
	private MediaSourceCleanupCron $mediaSourceCleanupCron;
	private AccountStatsCron $accountStatsCron;
	private AccountTokenCron $accountTokenCron;
	private AccountHooks $accountHooks;
	private FeedHooks $feedHooks;
	private SyncHooks $syncHooks;
	private FrontIndexHooks $frontIndexHooks;
	private GlobalFeedHooksRuntime $globalFeedHooks;
	private GlobalFeedRendererRuntime $globalFeedRenderer;
	private GlobalReconnectNoticeRenderer $globalReconnectNoticeRenderer;
	private HookDiagnostics $hookDiagnostics;

	public function __construct(
		Scheduler $scheduler,
		AccountStatsCron $accountStatsCron,
		AccountTokenCron $accountTokenCron,
		MediaQueueCron $mediaQueueCron,
		MediaSyncCron $mediaSyncCron,
		CronFallback $cronFallback,
		MediaSourceCleanupCron $mediaSourceCleanupCron,
		AccountHooks $accountHooks,
		FeedHooks $feedHooks,
		SyncHooks $syncHooks,
		FrontIndexHooks $frontIndexHooks,
		GlobalFeedHooksRuntime $globalFeedHooks,
		GlobalFeedRendererRuntime $globalFeedRenderer,
		GlobalReconnectNoticeRenderer $globalReconnectNoticeRenderer,
		HookDiagnostics $hookDiagnostics
	) {
		$this->scheduler                     = $scheduler;
		$this->accountStatsCron              = $accountStatsCron;
		$this->accountTokenCron              = $accountTokenCron;
		$this->mediaQueueCron                = $mediaQueueCron;
		$this->mediaSyncCron                 = $mediaSyncCron;
		$this->cronFallback                  = $cronFallback;
		$this->mediaSourceCleanupCron        = $mediaSourceCleanupCron;
		$this->accountHooks                  = $accountHooks;
		$this->feedHooks                     = $feedHooks;
		$this->syncHooks                     = $syncHooks;
		$this->frontIndexHooks               = $frontIndexHooks;
		$this->globalFeedHooks               = $globalFeedHooks;
		$this->globalFeedRenderer            = $globalFeedRenderer;
		$this->globalReconnectNoticeRenderer = $globalReconnectNoticeRenderer;
		$this->hookDiagnostics               = $hookDiagnostics;
	}

	public function init(): void {
		$this->cleanupLegacyCrons();

		$this->scheduler->registerIntervals();
		$this->accountStatsCron->register();
		$this->accountTokenCron->register();
		$this->mediaQueueCron->register();
		$this->mediaSyncCron->register();
		$this->cronFallback->register();
		$this->mediaSourceCleanupCron->register();
		$this->accountHooks->register();
		$this->feedHooks->register();
		$this->syncHooks->register();
		$this->frontIndexHooks->register();
		$this->globalFeedHooks->register();
		$this->globalFeedRenderer->register();
		$this->globalReconnectNoticeRenderer->register();
		$this->hookDiagnostics->reportMissing();
		$this->scheduler->scheduleRecurring( MediaSyncCron::HOOK, 'hourly' );
		$this->scheduler->scheduleRecurring( MediaSourceCleanupCron::HOOK, 'weekly' );
		$this->scheduler->scheduleRecurring( AccountStatsCron::HOOK, 'hourly' );
		$this->scheduler->scheduleRecurring( AccountTokenCron::HOOK, 'weekly' );
	}

	private function cleanupLegacyCrons(): void {
		$done = get_option( self::LEGACY_CRON_CLEANUP_OPTION, false );
		if ( $done ) {
			return;
		}

		wp_clear_scheduled_hook( 'inavii_social_feed_update_media' );
		wp_clear_scheduled_hook( 'inavii_social_feed_refresh_token' );
		update_option( self::LEGACY_CRON_CLEANUP_OPTION, 1, false );
	}
}
