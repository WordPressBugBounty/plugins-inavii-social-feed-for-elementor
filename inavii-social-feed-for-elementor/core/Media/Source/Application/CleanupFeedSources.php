<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Application;

use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Storage\MediaRepository;
use Inavii\Instagram\Media\Source\Domain\HashtagCleanupPolicy;

final class CleanupFeedSources {
	private MediaRepository $media;
	private MediaFileService $files;
	private HashtagCleanupPolicy $hashtagCleanup;

	public function __construct(
		MediaRepository $media,
		MediaFileService $files,
		HashtagCleanupPolicy $hashtagCleanup
	) {
		$this->media          = $media;
		$this->files          = $files;
		$this->hashtagCleanup = $hashtagCleanup;
	}

	public function handle( int $feedId ): void {
		if ( $feedId <= 0 ) {
			throw new \InvalidArgumentException( 'Feed id must be > 0.' );
		}

		$sourceIds = $this->media->feedSources()->getSourceIdsByFeedId( $feedId );
		$sources   = $this->media->sources()->getByIds( $sourceIds );

		$this->media->feedSources()->removeByFeedId( $feedId );

		$this->cleanupHashtagSources( $sources );
	}

	/**
	 * @param array $sources
	 */
	private function cleanupHashtagSources( array $sources ): void {
		foreach ( $sources as $row ) {
			$sourceId = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $sourceId <= 0 ) {
				continue;
			}

			$useCount = $this->media->feedSources()->countBySourceId( $sourceId );
			if ( ! $this->hashtagCleanup->canDeleteSource( $row, $useCount ) ) {
				continue;
			}

			$sourceKey = (string) $row['source_key'];
			$this->deletePostsBySourceKey( $sourceKey );
			$this->media->sources()->deleteById( $sourceId );
		}
	}

	private function deletePostsBySourceKey( string $sourceKey ): void {
		$rows = $this->media->posts()->getFilesBySourceKey( $sourceKey );

		$parentIds = $this->files->deletePostFilesWithChildren( $rows );
		if ( $parentIds !== [] ) {
			$this->media->files()->children->deleteByParentIds( $parentIds );
		}

		$this->media->posts()->deleteBySourceKey( $sourceKey );
	}
}
