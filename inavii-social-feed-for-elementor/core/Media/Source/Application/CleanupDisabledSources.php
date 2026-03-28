<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Source\Application;

use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class CleanupDisabledSources {

	private MediaRepository $repository;
	private MediaFileService $files;

	public function __construct( MediaRepository $repository, MediaFileService $files ) {
		$this->repository = $repository;
		$this->files      = $files;
	}

	public function handle( int $days = 7 ): void {
		$sources = $this->repository->sources()->getDisabledOlderThan( $days );
		foreach ( $sources as $row ) {
			$sourceKey = isset( $row['source_key'] ) ? (string) $row['source_key'] : '';
			$sourceId  = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $sourceKey === '' || $sourceId <= 0 ) {
				continue;
			}

			$this->deletePostsBySourceKey( $sourceKey );
			$this->repository->sources()->deleteById( $sourceId );
		}
	}

	private function deletePostsBySourceKey( string $sourceKey ): void {
		$rows = $this->repository->posts()->getFilesBySourceKey( $sourceKey );

		$parentIds = $this->files->deletePostFilesWithChildren( $rows );
		if ( $parentIds !== [] ) {
			$this->repository->files()->children->deleteByParentIds( $parentIds );
		}

		$this->repository->posts()->deleteBySourceKey( $sourceKey );
	}
}
