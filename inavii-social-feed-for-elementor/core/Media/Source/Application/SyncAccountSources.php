<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Application;

use Inavii\Instagram\Media\Storage\MediaRepository;

final class SyncAccountSources {
	private MediaRepository $repository;
	private SyncSources $sync;

	public function __construct( MediaRepository $repository, SyncSources $sync ) {
		$this->repository = $repository;
		$this->sync       = $sync;
	}

	public function handle( int $accountId ): int {
		if ( $accountId <= 0 ) {
			return 0;
		}

		$this->repository->sources()->clearFailuresByAccountId( $accountId );
		$rows = $this->repository->sources()->getSourcesByAccountIds( [ $accountId ] );

		if ( $rows === [] ) {
			return 0;
		}

		$this->sync->handleRows( $rows );

		return count( $rows );
	}
}
