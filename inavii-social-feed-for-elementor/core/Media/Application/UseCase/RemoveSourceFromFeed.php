<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application\UseCase;

use Inavii\Instagram\Media\Storage\MediaRepository;

final class RemoveSourceFromFeed {

	private MediaRepository $repository;

	public function __construct( MediaRepository $repository ) {
		$this->repository = $repository;
	}

	public function handle( int $feedId, int $sourceId ): void {
		$this->repository->feedSources()->remove( $feedId, $sourceId );

		if ( $this->repository->feedSources()->countBySourceId( $sourceId ) === 0 ) {
			$this->repository->sources()->disable( $sourceId );
		}
	}
}
