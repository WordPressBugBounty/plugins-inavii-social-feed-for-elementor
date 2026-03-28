<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application\UseCase;

use Inavii\Instagram\Media\Domain\MediaRetentionPolicy;
use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class CleanupOldMedia {
	private MediaRepository $repository;
	private MediaFileService $files;
	private MediaRetentionPolicy $retention;
	private TrimSourceOverflow $trimSourceOverflow;

	public function __construct(
		MediaRepository $repository,
		MediaFileService $files,
		MediaRetentionPolicy $retention,
		TrimSourceOverflow $trimSourceOverflow
	) {
		$this->repository         = $repository;
		$this->files              = $files;
		$this->retention          = $retention;
		$this->trimSourceOverflow = $trimSourceOverflow;
	}

	public function handle(): void {
		$now     = time();
		$sources = $this->repository->sources()->getActiveWithLastSuccess();

		foreach ( $sources as $row ) {
			$sourceKey   = isset( $row['source_key'] ) ? (string) $row['source_key'] : '';
			$kind        = isset( $row['kind'] ) ? (string) $row['kind'] : '';
			$lastSuccess = isset( $row['last_success_at'] ) ? (string) $row['last_success_at'] : '';

			if ( $sourceKey === '' || $kind === '' || $lastSuccess === '' ) {
				continue;
			}

			$this->trimSourceOverflow->handle( $sourceKey, $kind );

			$days = $this->retention->resolveDays( $kind, $sourceKey );
			if ( $days <= 0 ) {
				continue;
			}

			if ( ! $this->retention->shouldCleanupSource( $lastSuccess, $days, $now ) ) {
				continue;
			}

			$cutoff = $this->retention->cutoffDateTime( $days, $now );

			// last_seen_at is refreshed on every successful fetch for items returned by the API.
			// Anything not seen for longer than the retention window is treated as stale.
			$rows = $this->repository->posts()->getFilesBySourceKeySeenBefore( $sourceKey, $cutoff );
			if ( $rows === [] ) {
				continue;
			}

			$this->deletePostsByRows( $rows );
		}
	}

	/**
	 * @param array $rows Rows loaded from media table.
	 */
	private function deletePostsByRows( array $rows ): void {
		$parentIds = $this->files->deletePostFilesWithChildren( $rows );
		if ( $parentIds === [] ) {
			return;
		}

		$this->repository->files()->children->deleteByParentIds( $parentIds );
		$this->repository->posts()->deleteByIds( $parentIds );
	}
}
