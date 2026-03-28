<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application\UseCase;

use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Source\Domain\SourceRetentionPolicy;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class TrimSourceOverflow {
	private MediaRepository $repository;
	private MediaFileService $files;
	private SourceRetentionPolicy $policy;

	public function __construct(
		MediaRepository $repository,
		MediaFileService $files,
		SourceRetentionPolicy $policy
	) {
		$this->repository = $repository;
		$this->files      = $files;
		$this->policy     = $policy;
	}

	public function handle( string $sourceKey, string $sourceKind ): int {
		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return 0;
		}

		$maxItems = $this->policy->maxItems( $sourceKind, $sourceKey );
		if ( $maxItems <= 0 ) {
			return 0;
		}

		$currentCount = $this->repository->posts()->countBySourceKey( $sourceKey );
		$overflow     = $this->policy->overflowCount( $currentCount, $maxItems );
		if ( $overflow <= 0 ) {
			return 0;
		}

		$rows = $this->repository->posts()->getOldestFilesBySourceKey( $sourceKey, $overflow );
		if ( $rows === [] ) {
			return 0;
		}

		$ids = $this->extractIds( $rows );
		if ( $ids === [] ) {
			return 0;
		}

		$parentIds = $this->files->deletePostFilesWithChildren( $rows );
		if ( $parentIds !== [] ) {
			$this->repository->files()->children->deleteByParentIds( $parentIds );
		}

		$this->repository->posts()->deleteByIds( $ids );

		return count( $ids );
	}

	private function extractIds( array $rows ): array {
		$ids = [];

		foreach ( $rows as $row ) {
			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
