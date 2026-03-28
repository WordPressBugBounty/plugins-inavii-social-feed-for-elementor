<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

use Inavii\Instagram\Media\Storage\MediaRepository;

final class MediaQueue {

	private MediaRepository $repository;

	public function __construct( MediaRepository $repository ) {
		$this->repository = $repository;
	}

	public function updateStaleDownloading( int $staleMinutes = 30, int $limit = 200 ): void {
		$this->repository->files()->recoverStuckDownloads( $staleMinutes, $limit );
	}

	/**
	 * @return array
	 */
	public function findBatch( int $batchSize = 20 ): array {
		return $this->repository->files()->getQueued( $batchSize );
	}

	public function countQueued(): int {
		return $this->repository->files()->countQueued();
	}
}
