<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Cron;

use Inavii\Instagram\Cron\Lock;
use Inavii\Instagram\Media\Source\Application\CleanupDisabledSources;
use Inavii\Instagram\Media\Application\UseCase\CleanupOldMedia;
use Inavii\Instagram\Media\Source\Domain\SourceCleanupPolicy;
use Inavii\Instagram\Logger\Logger;

final class MediaSourceCleanupCron {

	public const HOOK = 'inavii/social-feed/media/source_cleanup';

	private const LOCK_TTL_SECONDS = 600;

	private CleanupDisabledSources $cleanupDisabledSources;
	private CleanupOldMedia $cleanupOldMedia;
	private SourceCleanupPolicy $sourceCleanupPolicy;

	public function __construct(
		CleanupDisabledSources $cleanupDisabledSources,
		CleanupOldMedia $cleanupOldMedia,
		SourceCleanupPolicy $sourceCleanupPolicy
	) {
		$this->cleanupDisabledSources = $cleanupDisabledSources;
		$this->cleanupOldMedia        = $cleanupOldMedia;
		$this->sourceCleanupPolicy    = $sourceCleanupPolicy;
	}

	public function register(): void {
		add_action( self::HOOK, [ $this, 'run' ] );
	}

	public function run(): void {
		$lock = new Lock( 'media_source_cleanup', self::LOCK_TTL_SECONDS );

		if ( ! $lock->lock() ) {
			return;
		}

		try {
			try {
				$this->cleanupDisabledSources->handle( $this->sourceCleanupPolicy->disabledCleanupDays() );
				$this->cleanupOldMedia->handle();
			} catch ( \Throwable $e ) {
				Logger::error(
					'cron/media_cleanup',
					'Media cleanup failed.',
					[
						'error' => $e->getMessage(),
					]
				);
			}
		} finally {
			$lock->unlock();
		}
	}
}
