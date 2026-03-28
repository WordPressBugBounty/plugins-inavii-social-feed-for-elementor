<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Migration;

final class LegacyMigrationQueue {
	public const HOOK = 'inavii/social-feed/legacy/migration';

	private const DEFAULT_DELAY_SECONDS = 30;
	private const INLINE_COOLDOWN_SECONDS = 10;
	private const INLINE_COOLDOWN_KEY     = 'inavii_social_feed_legacy_migration_inline_cooldown';

	private LegacyDataMigrator $migrator;

	public function __construct( LegacyDataMigrator $migrator ) {
		$this->migrator = $migrator;
	}

	public function register(): void {
		add_action( self::HOOK, [ $this, 'runFull' ] );
	}

	public function maybeScheduleFull(): void {
		if ( $this->migrator->isFullDone() ) {
			return;
		}

		$this->schedule( self::DEFAULT_DELAY_SECONDS );
		$this->maybeRunInline();
	}

	public function runFull(): void {
		if ( $this->migrator->isFullDone() ) {
			return;
		}

		$this->migrator->maybeRunFull();

		if ( ! $this->migrator->isFullDone() ) {
			$this->schedule( self::DEFAULT_DELAY_SECONDS );
		}
	}

	private function schedule( int $delaySeconds ): void {
		$delaySeconds = max( 10, $delaySeconds );

		if ( wp_next_scheduled( self::HOOK ) !== false ) {
			return;
		}

		wp_schedule_single_event( time() + $delaySeconds, self::HOOK );
	}

	private function maybeRunInline(): void {
		if ( wp_doing_cron() ) {
			return;
		}

		if ( ! is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( get_transient( self::INLINE_COOLDOWN_KEY ) !== false ) {
			return;
		}

		set_transient( self::INLINE_COOLDOWN_KEY, '1', self::INLINE_COOLDOWN_SECONDS );
		$this->runFull();
	}
}
