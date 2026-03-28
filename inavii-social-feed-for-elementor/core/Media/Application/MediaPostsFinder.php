<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application;

use Inavii\Instagram\Media\Application\Mapper\MediaChildrenMapper;
use Inavii\Instagram\Media\Dto\MediaItemDto;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class MediaPostsFinder {

	private MediaRepository $repository;
	private MediaChildrenMapper $childrenMapper;

	public function __construct( MediaRepository $repository, MediaChildrenMapper $childrenMapper ) {
		$this->repository     = $repository;
		$this->childrenMapper = $childrenMapper;
	}

	public function bySourceKey(
		string $sourceKey,
		int $limit = 30,
		?string $cursorPostedAt = null,
		?int $cursorId = null
	): array {
		$items = $this->repository->posts()->getBySourceKey( $sourceKey, $limit, $cursorPostedAt, $cursorId );

		$childrenMap = $this->childrenMapper->mapByParents( $items );

		return MediaItemDto::fromDbRows( $items, $childrenMap );
	}

	public function bySourceKeys( array $sourceKeys, int $limit = 30, int $offset = 0 ): array {
		$items = $this->repository->posts()->getBySourceKeys( $sourceKeys, $limit, $offset );

		$childrenMap = $this->childrenMapper->mapByParents( $items );

		return MediaItemDto::fromDbRows( $items, $childrenMap );
	}

	public function bySourceKeysFiltered( array $sourceKeys, array $filters, int $limit = 30, int $offset = 0 ): array {
		$items = $this->repository->posts()->getBySourceKeysFiltered( $sourceKeys, $filters, $limit, $offset );

		$childrenMap = $this->childrenMapper->mapByParents( $items );

		return MediaItemDto::fromDbRows( $items, $childrenMap );
	}

	public function byIds( array $ids ): array {
		$items = $this->repository->posts()->getByIds( $ids );

		$childrenMap = $this->childrenMapper->mapByParents( $items );

		return MediaItemDto::fromDbRows( $items, $childrenMap );
	}

	public function countBySourceKeys( array $sourceKeys ): int {
		return $this->repository->posts()->countBySourceKeys( $sourceKeys );
	}

	public function countBySourceKeysFiltered( array $sourceKeys, array $filters ): int {
		return $this->repository->posts()->countBySourceKeysFiltered( $sourceKeys, $filters );
	}

	public function lastSeenAtBySourceKeys( array $sourceKeys ): array {
		return $this->repository->posts()->getLastSeenAtBySourceKeys( $sourceKeys );
	}
}
