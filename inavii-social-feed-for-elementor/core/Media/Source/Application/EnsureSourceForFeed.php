<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Source\Application;

use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class EnsureSourceForFeed {

	private MediaRepository $repository;

	public function __construct( MediaRepository $repository ) {
		$this->repository = $repository;
	}

	public function handle( int $feedId, Source $source, string $fetchKey ): int {
		$sourceKey = $source->kind() === Source::KIND_ACCOUNT
			? Source::accountSourceKey( (string) $fetchKey )
			: $source->dbSourceKey();

		$accountId = $source->kind() === Source::KIND_ACCOUNT ? $source->accountId() : null;

		$sourceId = $this->repository->sources()->save(
			$source->kind(),
			$sourceKey,
			$accountId,
			$fetchKey
		);

		if ( $feedId > 0 && $sourceId > 0 ) {
			$this->repository->feedSources()->add( $feedId, $sourceId );
		}

		return $sourceId;
	}
}
