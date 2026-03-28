<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application\UseCase;

use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Domain\MediaPost;
use Inavii\Instagram\Media\Domain\MissingMediaCleanupPolicy;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class CleanupMissingInFetchedWindow {
	private const DELETE_CHUNK_SIZE = 30;

	private MediaRepository $repository;
	private MediaFileService $files;
	private MissingMediaCleanupPolicy $policy;

	public function __construct(
		MediaRepository $repository,
		MediaFileService $files,
		MissingMediaCleanupPolicy $policy
	) {
		$this->repository = $repository;
		$this->files      = $files;
		$this->policy     = $policy;
	}

	/**
	 * @param string      $sourceKey  Source key in storage.
	 * @param string      $sourceKind Source kind (accounts/tagged/hashtag).
	 * @param MediaPost[] $posts
	 */
	public function handle( string $sourceKey, string $sourceKind, array $posts ): int {
		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return 0;
		}

		if ( ! $this->policy->enabled( $sourceKind, $sourceKey ) ) {
			return 0;
		}

		$window = $this->collectSeenWindow( $posts );
		if ( $window['oldestPostedAt'] === '' || $window['mediaIds'] === [] ) {
			return 0;
		}

		$limit = $this->policy->deleteLimit( $sourceKind, $sourceKey );
		$rows  = $this->repository->posts()->getMissingBySourceKeySince(
			$sourceKey,
			$window['mediaIds'],
			$window['oldestPostedAt'],
			$limit
		);

		if ( $rows === [] ) {
			return 0;
		}

		$timeBudget = $this->policy->timeBudgetSeconds( $sourceKind, $sourceKey );
		$startedAt  = microtime( true );
		$deleted    = 0;
		foreach ( array_chunk( $rows, self::DELETE_CHUNK_SIZE ) as $chunk ) {
			if ( ( microtime( true ) - $startedAt ) >= $timeBudget ) {
				break;
			}

			$parentIds = $this->files->deletePostFilesWithChildren( $chunk );
			if ( $parentIds === [] ) {
				continue;
			}

			$this->repository->files()->children->deleteByParentIds( $parentIds );
			$this->repository->posts()->deleteByIds( $parentIds );
			$deleted += count( $parentIds );
		}

		return $deleted;
	}

	/**
	 * @param MediaPost[] $posts
	 * @return array{mediaIds:string[],oldestPostedAt:string}
	 */
	private function collectSeenWindow( array $posts ): array {
		$mediaIds       = [];
		$oldestPostedAt = '';

		foreach ( $posts as $post ) {
			if ( ! $post instanceof MediaPost ) {
				continue;
			}

			$row      = $post->toDbRow();
			$mediaId  = isset( $row['ig_media_id'] ) ? trim( (string) $row['ig_media_id'] ) : '';
			$postedAt = isset( $row['posted_at'] ) ? trim( (string) $row['posted_at'] ) : '';

			if ( $mediaId === '' || $postedAt === '' ) {
				continue;
			}

			$mediaIds[] = $mediaId;
			if ( $oldestPostedAt === '' || strcmp( $postedAt, $oldestPostedAt ) < 0 ) {
				$oldestPostedAt = $postedAt;
			}
		}

		return [
			'mediaIds'       => array_values( array_unique( $mediaIds ) ),
			'oldestPostedAt' => $oldestPostedAt,
		];
	}
}
