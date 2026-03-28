<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Application\UseCase;

use Inavii\Instagram\Front\Application\FrontIndexReader;
use Inavii\Instagram\Front\Domain\Policy\PreloadMediaPolicy;
use Inavii\Instagram\Media\Application\MediaAccountProfileHydrator;
use Inavii\Instagram\Media\Application\MediaProfilesProjection;
use Inavii\Instagram\Media\Application\MediaPostsFinder;

final class GetFrontMediaPage {
	public const DEFAULT_PAGE_SIZE = PreloadMediaPolicy::DEFAULT_PAGE_SIZE;

	private FrontIndexReader $index;
	private MediaPostsFinder $finder;
	private MediaAccountProfileHydrator $profiles;
	private MediaProfilesProjection $mediaProfiles;
	private PreloadMediaPolicy $preloadPolicy;

	public function __construct(
		FrontIndexReader $index,
		MediaPostsFinder $finder,
		MediaAccountProfileHydrator $profiles,
		MediaProfilesProjection $mediaProfiles,
		PreloadMediaPolicy $preloadPolicy
	) {
		$this->index         = $index;
		$this->finder        = $finder;
		$this->profiles      = $profiles;
		$this->mediaProfiles = $mediaProfiles;
		$this->preloadPolicy = $preloadPolicy;
	}

	public function handle(
		int $feedId,
		int $limit = self::DEFAULT_PAGE_SIZE,
		int $offset = 0
	): array {
		if ( $feedId <= 0 ) {
			return [];
		}

		if ( $offset < 0 ) {
			$offset = 0;
		}

		$limit = $this->preloadPolicy->resolvePageLimit( $limit );

		$index = $this->index->load( $feedId );
		if ( $index === [] ) {
			return [];
		}

		$meta  = isset( $index['meta'] ) && is_array( $index['meta'] ) ? $index['meta'] : [];
		$media = isset( $index['media'] ) && is_array( $index['media'] ) ? $index['media'] : [];
		$ids   = $this->index->extractMediaIds( $index, $media );

		$total = isset( $meta['total'] ) ? (int) $meta['total'] : count( $ids );
		if ( $total < count( $ids ) ) {
			$total = count( $ids );
		}

		$sliceIds  = array_slice( $ids, $offset, $limit );
		$items     = $sliceIds === [] ? [] : $this->profiles->hydrate( $this->finder->byIds( $sliceIds ) );
		$projected = $this->mediaProfiles->project( $items );
		$items     = isset( $projected['media'] ) && is_array( $projected['media'] ) ? $projected['media'] : [];
		$profiles  = isset( $projected['profiles'] ) && is_array( $projected['profiles'] ) ? $projected['profiles'] : [];

		$payload = [
			'media'    => $items,
			'profiles' => $profiles,
			'total'    => $total,
			'offset'   => $offset,
			'limit'    => $limit,
			'count'    => count( $items ),
			'hasMore'  => ( $offset + count( $items ) ) < $total,
			'feedId'   => $feedId,
		];

		return $payload;
	}
}
