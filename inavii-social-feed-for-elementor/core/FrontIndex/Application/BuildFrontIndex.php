<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application;

use Inavii\Instagram\Feed\Application\FeedService;
use Inavii\Instagram\FrontIndex\Domain\Policy\PayloadCountPolicy;
use Inavii\Instagram\Media\Application\MediaProfilesProjection;

final class BuildFrontIndex {
	private FeedService $feeds;
	private FrontIndexMetaBuilder $metaBuilder;
	private MediaProfilesProjection $mediaProfiles;
	private PayloadCountPolicy $payloadCountPolicy;
	private MediaIdsCollector $mediaIds;

	public function __construct(
		FeedService $feeds,
		FrontIndexMetaBuilder $metaBuilder,
		MediaProfilesProjection $mediaProfiles,
		PayloadCountPolicy $payloadCountPolicy,
		MediaIdsCollector $mediaIds
	) {
		$this->feeds              = $feeds;
		$this->metaBuilder        = $metaBuilder;
		$this->mediaProfiles      = $mediaProfiles;
		$this->payloadCountPolicy = $payloadCountPolicy;
		$this->mediaIds           = $mediaIds;
	}

	public function handle( int $feedId, int $mediaLimit = 40 ): array {
		$mediaLimit = $this->normalizeLimit( $mediaLimit );
		$front      = $this->feeds->getForFrontApp( $feedId, $mediaLimit, 0 );

		$feed      = isset( $front['feed'] ) && is_array( $front['feed'] ) ? $front['feed'] : [];
		$media     = isset( $front['media'] ) && is_array( $front['media'] ) ? $front['media'] : [];
		$total     = isset( $front['total'] ) ? (int) $front['total'] : count( $media );
		$projected = $this->mediaProfiles->project( $media );
		$media     = isset( $projected['media'] ) && is_array( $projected['media'] ) ? $projected['media'] : [];
		$profiles  = isset( $projected['profiles'] ) && is_array( $projected['profiles'] ) ? $projected['profiles'] : [];

		$meta                 = $this->metaBuilder->build( $feed, $total );
		$meta['profiles']     = $profiles;
		$meta['payloadCount'] = $mediaLimit;
		$ids                  = $this->mediaIds->collect(
			$media,
			$total,
			function ( int $limit, int $offset ) use ( $feedId ): array {
				return $this->feeds->getForFrontApp( $feedId, $limit, $offset );
			}
		);

		return [
			'meta'     => $meta,
			'media'    => $media,
			'mediaIds' => $ids,
		];
	}

	public function recommendPayloadLimit( int $feedId, int $baseLimit ): int {
		$baseLimit = $this->normalizeLimit( $baseLimit );
		if ( $feedId <= 0 ) {
			return $baseLimit;
		}

		try {
			$feed = $this->feeds->getForAdminApp( $feedId );
		} catch ( \Throwable $e ) {
			return $baseLimit;
		}

		if ( ! is_array( $feed ) ) {
			return $baseLimit;
		}

		return $this->normalizeLimit(
			$this->payloadCountPolicy->resolve( $feed, $baseLimit )
		);
	}

	private function normalizeLimit( int $limit ): int {
		if ( $limit < 1 ) {
			return 1;
		}

		if ( $limit > 200 ) {
			return 200;
		}

		return $limit;
	}
}
