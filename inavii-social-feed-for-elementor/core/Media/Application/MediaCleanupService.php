<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application;

use Inavii\Instagram\Media\Storage\MediaRepository;
use Inavii\Instagram\Media\Source\Domain\SourceCleanupPolicy;

final class MediaCleanupService {

	private MediaRepository $media;
	private MediaFileService $files;
	private SourceCleanupPolicy $sourceCleanup;

	public function __construct(
		MediaRepository $media,
		MediaFileService $files,
		SourceCleanupPolicy $sourceCleanup
	) {
		$this->media         = $media;
		$this->files         = $files;
		$this->sourceCleanup = $sourceCleanup;
	}

	public function unpinSource( string $sourceKey ): void {
		$this->media->sources()->removePinnedByKey( $sourceKey );
	}

	public function detachSourceFromFeeds( string $sourceKey ): void {
		$source = $this->media->sources()->getByKey( $sourceKey );
		if ( ! is_array( $source ) ) {
			return;
		}

		$sourceId = isset( $source['id'] ) ? (int) $source['id'] : 0;
		if ( $sourceId <= 0 ) {
			return;
		}

		$this->media->feedSources()->removeBySourceId( $sourceId );
	}

	public function cleanupSourceIfUnused( string $sourceKey ): void {
		$source = $this->media->sources()->getByKey( $sourceKey );
		if ( ! is_array( $source ) ) {
			return;
		}

		$sourceId = isset( $source['id'] ) ? (int) $source['id'] : 0;
		if ( $sourceId <= 0 ) {
			return;
		}

		$inUseCount = $this->media->feedSources()->countBySourceId( $sourceId );
		if ( ! $this->sourceCleanup->canDeleteUnpinnedUnusedSource( $source, $inUseCount ) ) {
			return;
		}

		$this->deletePostsBySourceKey( $sourceKey );
		$this->media->sources()->deleteById( $sourceId );
	}

	public function deletePostsBySourceKey( string $sourceKey ): void {
		$rows = $this->media->posts()->getFilesBySourceKey( $sourceKey );

		$parentIds = $this->files->deletePostFilesWithChildren( $rows );
		if ( $parentIds !== [] ) {
			$this->media->files()->children->deleteByParentIds( $parentIds );
		}

		$this->media->posts()->deleteBySourceKey( $sourceKey );
	}
}
