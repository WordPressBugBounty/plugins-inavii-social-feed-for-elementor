<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application\UseCase;

use Inavii\Instagram\Media\Domain\MediaPost;
use Inavii\Instagram\Media\Domain\SyncError;
use Inavii\Instagram\Media\Domain\SyncResult;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Fetcher\MediaSourceFetcher;
use Inavii\Instagram\Media\Storage\MediaRepository;
use Inavii\Instagram\Media\Application\UseCase\EnqueueMediaFiles;
use Inavii\Instagram\Media\Application\UseCase\TrimSourceOverflow;
use Inavii\Instagram\Media\Cron\MediaQueueCron;
use Inavii\Instagram\InstagramApi\InstagramApiException;

final class FetchMedia {
	private MediaSourceFetcher $fetcher;
	private MediaRepository $repository;
	private EnqueueMediaFiles $queuePostFiles;
	private MediaQueueCron $queueCron;
	private TrimSourceOverflow $trimSourceOverflow;
	private CleanupMissingInFetchedWindow $cleanupMissingInFetchedWindow;

	public function __construct(
		MediaSourceFetcher $fetcher,
		MediaRepository $repository,
		EnqueueMediaFiles $queuePostFiles,
		MediaQueueCron $queueCron,
		TrimSourceOverflow $trimSourceOverflow,
		CleanupMissingInFetchedWindow $cleanupMissingInFetchedWindow
	) {
		$this->fetcher                       = $fetcher;
		$this->repository                    = $repository;
		$this->queuePostFiles                = $queuePostFiles;
		$this->queueCron                     = $queueCron;
		$this->trimSourceOverflow            = $trimSourceOverflow;
		$this->cleanupMissingInFetchedWindow = $cleanupMissingInFetchedWindow;
	}

	public function handle( Source $source ): SyncResult {
		return $this->handleMany( [ $source ] );
	}

	/**
	 * @param Source[] $sources List of sources to fetch.
	 */
	private function handleMany( array $sources ): SyncResult {
		$result = new SyncResult();
		$seen   = [];

		foreach ( $sources as $source ) {
			if ( ! $source instanceof Source ) {
				continue;
			}

			$sourceLabel = $this->sourceLabel( $source );
			if ( isset( $seen[ $sourceLabel ] ) ) {
				continue;
			}
			$seen[ $sourceLabel ] = true;

			$result->sourcesTotal++;

			try {
				do_action( 'inavii/social-feed/media/sync/started', $sourceLabel, $source->kind() );
				$fetchResponse = $this->fetcher->fetch( $source );
				$sourceKey     = $fetchResponse->sourceKey();

				$items                 = $fetchResponse->items();
				$result->itemsFetched += count( $items );

				$posts = MediaPost::fromInstagramList( $items );

				$savedCount = 0;
				foreach ( array_chunk( $posts, 30 ) as $chunk ) {
					$savedCount += $this->repository->posts()->save( $sourceKey, $chunk );
				}
				$result->itemsSaved += $savedCount;

				$this->trimSourceOverflow->handle( $sourceKey, $source->kind() );
				$this->cleanupMissingInFetchedWindow->handle( $sourceKey, $source->kind(), $posts );

				if ( $this->shouldImportFiles( $sourceKey, $source->kind() ) ) {
					try {
						$enqueued = $this->queuePostFiles->handle( $sourceKey, count( $posts ) );
						if ( $enqueued > 0 ) {
							$this->queueCron->schedule();
						}
					} catch ( \Throwable $e ) {
						$result->addError(
							new SyncError(
								0,
								$sourceLabel,
								'File queue error: ' . $e->getMessage()
							)
						);
					}
				}

				$result->sourcesOk++;
				$this->repository->sources()->markSyncSuccessByKey( $sourceKey );
				do_action( 'inavii/social-feed/media/sync/finished', $sourceKey, $savedCount, count( $items ) );
				do_action( 'inavii/social-feed/front-index/rebuild', [ 'sourceKey' => $sourceKey ] );
			} catch ( \Throwable $e ) {
				$authFailure = false;
				if ( $e instanceof InstagramApiException && $e->requiresReconnect() ) {
					$authFailure = true;
				}

				do_action( 'inavii/social-feed/media/sync/error', $sourceLabel, $e->getMessage() );
				$result->addError(
					new SyncError(
						0,
						$sourceLabel,
						$e->getMessage(),
						$authFailure
					)
				);
			}
		}

		return $result;
	}

	private function sourceLabel( Source $source ): string {
		if ( $source->kind() === Source::KIND_ACCOUNT ) {
			return 'accounts:' . (string) $source->accountId();
		}

		return $source->dbSourceKey();
	}

	private function shouldImportFiles( string $sourceKey, string $sourceKind ): bool {
		return (bool) apply_filters(
			'inavii/social-feed/media/import_files',
			true,
			$sourceKind,
			$sourceKey
		);
	}
}
