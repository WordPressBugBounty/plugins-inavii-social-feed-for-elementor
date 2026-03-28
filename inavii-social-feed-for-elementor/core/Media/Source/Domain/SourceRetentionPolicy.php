<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Source\Domain;

final class SourceRetentionPolicy {
	private const DEFAULT_MAX_ITEMS = 50;
	private const HASHTAG_MAX_ITEMS = 200;

	public function maxItems( string $sourceKind, string $sourceKey ): int {
		$sourceKind = trim( $sourceKind );
		$sourceKey  = trim( $sourceKey );

		$max = $this->resolveGlobalDefaultMax( $sourceKind, $sourceKey );

		if ( function_exists( 'apply_filters' ) ) {
			$max = (int) apply_filters(
				'inavii/social-feed/media/source/max_items',
				$max,
				$sourceKind,
				$sourceKey
			);
		}

		$max = $this->normalizeMax( $max );
		return $this->enforceKindCeilings( $max, $sourceKind );
	}

	public function overflowCount( int $currentCount, int $maxItems ): int {
		if ( $maxItems <= 0 ) {
			return 0;
		}

		if ( $currentCount <= $maxItems ) {
			return 0;
		}

		return $currentCount - $maxItems;
	}

	private function resolveGlobalDefaultMax( string $sourceKind, string $sourceKey ): int {
		$globalDefault = self::DEFAULT_MAX_ITEMS;

		if ( function_exists( 'get_option' ) ) {
			$globalDefault = (int) get_option( 'inavii_social_feed_number_of_posts_imported', self::DEFAULT_MAX_ITEMS );
		}

		if ( function_exists( 'apply_filters' ) ) {
			$globalDefault = (int) apply_filters( 'inavii/social-feed/media/fetch_limit', $globalDefault );
			$globalDefault = (int) apply_filters(
				'inavii/social-feed/media/source/default_max_items',
				$globalDefault,
				$sourceKind,
				$sourceKey
			);
		}

		return $this->normalizeMax( $globalDefault );
	}

	private function normalizeMax( int $max ): int {
		if ( $max <= 0 ) {
			return 0;
		}

		if ( $max > 5000 ) {
			return 5000;
		}

		return $max;
	}

	private function enforceKindCeilings( int $max, string $sourceKind ): int {
		if ( $sourceKind === Source::KIND_HASHTAG && $max > self::HASHTAG_MAX_ITEMS ) {
			return self::HASHTAG_MAX_ITEMS;
		}

		return $max;
	}
}
