<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application\UseCase;

use Inavii\Instagram\Media\Storage\MediaRepository;

final class EnqueueMediaFiles {

	private MediaRepository $repository;

	public function __construct( MediaRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Enqueue latest items for a source and return number enqueued.
	 */
	public function handle( string $sourceKey, int $rowsCount ): int {
		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return 0;
		}

		$limit = $this->clampLimit( $rowsCount );
		if ( $limit === 0 ) {
			return 0;
		}

		return $this->repository->files()->queueForSource( $sourceKey, $limit );
	}

	private function clampLimit( int $limit ): int {
		if ( $limit <= 0 ) {
			return 0;
		}
		if ( $limit > 100 ) {
			return 100;
		}
		return $limit;
	}
}
