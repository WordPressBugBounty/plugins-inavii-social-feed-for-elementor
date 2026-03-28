<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Domain\Policy;

final class GlobalFeedVisibilityPolicy {
	public function canRenderOnRequest(
		bool $isAdmin,
		bool $isFeedRequest,
		bool $isRobotsRequest,
		bool $isTrackbackRequest
	): bool {
		return ! $isAdmin && ! $isFeedRequest && ! $isRobotsRequest && ! $isTrackbackRequest;
	}

	public function isEnabled( int $feedId ): bool {
		return (bool) apply_filters( 'inavii/social-feed/front/global/enabled', true, $feedId );
	}

	public function isExcludedPage( int $feedId, int $currentPageId ): bool {
		if ( $currentPageId <= 0 ) {
			return false;
		}

		$excludedPageIds = apply_filters( 'inavii/social-feed/front/global/excluded_page_ids', [], $feedId );
		if ( ! is_array( $excludedPageIds ) || $excludedPageIds === [] ) {
			return false;
		}

		$normalizedIds = $this->normalizeIds( $excludedPageIds );
		if ( $normalizedIds === [] ) {
			return false;
		}

		return in_array( $currentPageId, $normalizedIds, true );
	}

	/**
	 * @param array $rawIds
	 *
	 * @return array
	 */
	private function normalizeIds( array $rawIds ): array {
		return array_values(
			array_filter(
				array_map(
					static function ( $value ): int {
						return is_numeric( $value ) ? (int) $value : 0;
					},
					$rawIds
				),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		);
	}
}
