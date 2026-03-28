<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Cron;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Cron\Lock;
use Inavii\Instagram\Media\Application\MediaQueueService;
use Inavii\Instagram\Logger\Logger;

final class MediaQueueCron {
	public const HOOK = 'inavii/social-feed/media/batch';

	private const DEFAULT_DELAY_SECONDS = 120; // 2 min
	private const LOCK_TTL_SECONDS      = 600; // 10 min
	private const LOCK_FALLBACK_KEY     = 'inavii_social_feed_media_queue_batch';
	private const BATCH_SIZE            = 20;

	private MediaQueueService $queue;

	public function __construct( MediaQueueService $queue ) {
		$this->queue = $queue;
	}

	public function register(): void {
		add_action( self::HOOK, [ $this, 'run' ] );
	}

	public function schedule( int $delaySeconds = self::DEFAULT_DELAY_SECONDS ): void {
		$delaySeconds = max( 10, $delaySeconds );

		if ( wp_next_scheduled( self::HOOK ) !== false ) {
			return;
		}

		wp_schedule_single_event( time() + $delaySeconds, self::HOOK );
	}

	public function run(): void {
		$lock = new Lock( $this->lockKey(), self::LOCK_TTL_SECONDS );

		if ( ! $lock->lock() ) {
			return;
		}

		try {
			try {
				$processed = $this->queue->runBatch( self::BATCH_SIZE );

				if ( $processed > 0 || $this->queue->hasQueued() ) {
					$this->schedule( self::DEFAULT_DELAY_SECONDS );
				}
			} catch ( \Throwable $e ) {
				Logger::error(
					'cron/media_queue',
					'Media queue failed.',
					[
						'error' => $e->getMessage(),
					]
				);
			}
		} finally {
			$lock->unlock();
		}
	}

	private function lockKey(): string {
		$prefix = Plugin::prefix();
		if ( $prefix !== '' ) {
			return $prefix . 'media_queue_batch';
		}

		return self::LOCK_FALLBACK_KEY;
	}
}
