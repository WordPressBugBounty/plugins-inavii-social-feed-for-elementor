<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Cron;

use Inavii\Instagram\Cron\Lock;
use Inavii\Instagram\Media\Source\Application\SyncSources;
use Inavii\Instagram\Logger\Logger;

final class MediaSyncCron {

	public const HOOK                = 'inavii/social-feed/media/sync';
	public const LAST_RUN_OPTION     = 'inavii_media_sync_last_run';
	public const LAST_SUCCESS_OPTION = 'inavii_media_sync_last_success';

	private const LOCK_TTL_SECONDS = 600;

	private SyncSources $sync;

	public function __construct( SyncSources $sync ) {
		$this->sync = $sync;
	}

	public function register(): void {
		add_action( self::HOOK, [ $this, 'run' ] );
	}

	public function run(): void {
		$this->runWithLimit( 50 );
	}

	public function runWithLimit( int $limit ): void {
		$lock = new Lock( 'media_sync', self::LOCK_TTL_SECONDS );

		if ( ! $lock->lock() ) {
			return;
		}

		try {
			$this->markRun();
			try {
				$this->sync->handle( $limit );
				$this->markSuccess();
			} catch ( \Throwable $e ) {
				Logger::error(
					'cron/media_sync',
					'Media sync failed.',
					[
						'error' => $e->getMessage(),
					]
				);
			}
		} finally {
			$lock->unlock();
		}
	}

	private function markRun(): void {
		update_option( self::LAST_RUN_OPTION, time(), false );
	}

	private function markSuccess(): void {
		update_option( self::LAST_SUCCESS_OPTION, time(), false );
	}
}
