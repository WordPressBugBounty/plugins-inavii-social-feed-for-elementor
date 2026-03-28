<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Domain\Policy;

final class PreloadMediaPolicy {
	public const DEFAULT_PRELOAD_LIMIT = 40;
	public const DEFAULT_PAGE_SIZE     = 20;
	private const MAX_PRELOAD_LIMIT    = 200;

	/**
	 * @param array $meta Front index meta payload.
	 */
	public function resolveLimit( array $meta ): int {
		$defaultLimit = $this->resolveDefaultPreloadLimit();
		$limit        = isset( $meta['payloadCount'] ) ? (int) $meta['payloadCount'] : $defaultLimit;

		return $this->normalizeLimit( $limit, $defaultLimit );
	}

	private function resolveDefaultPreloadLimit(): int {
		$limit = (int) apply_filters( 'inavii/social-feed/front/preload_limit', self::DEFAULT_PRELOAD_LIMIT );

		return $this->normalizeLimit( $limit, self::DEFAULT_PRELOAD_LIMIT );
	}

	private function normalizeLimit( int $limit, int $fallback ): int {
		if ( $limit < 1 ) {
			return $fallback;
		}

		if ( $limit > self::MAX_PRELOAD_LIMIT ) {
			return self::MAX_PRELOAD_LIMIT;
		}

		return $limit;
	}

	public function resolvePageLimit( int $limit ): int {
		return $limit > 0 ? $limit : self::DEFAULT_PAGE_SIZE;
	}

	/**
	 * @param array $options Feed options payload.
	 * @param array $media Cached media rows.
	 * @param int   $limit Media limit. Set to 0 to use preload behavior.
	 * @param int   $offset Media offset.
	 * @param int   $preloadLimit Max number of items for preload mode.
	 *
	 * @return array
	 */
	public function resolveMediaSlice( array $options, array $media, int $limit, int $offset, int $preloadLimit ): array {
		if ( $limit > 0 ) {
			return array_slice( $media, max( 0, $offset ), $limit );
		}

		if ( ! $this->isPreloadEnabled( $options ) ) {
			return [];
		}

		return array_slice( $media, 0, $preloadLimit );
	}

	/**
	 * @param array $options Feed options payload.
	 * @param array $mediaIds Full media IDs list.
	 * @param int   $limit Media limit. Set to 0 to use preload behavior.
	 * @param int   $offset Media offset.
	 * @param int   $preloadLimit Max number of items for preload mode.
	 *
	 * @return array
	 */
	public function resolveIdsSlice( array $options, array $mediaIds, int $limit, int $offset, int $preloadLimit ): array {
		if ( $limit > 0 ) {
			return array_slice( $mediaIds, max( 0, $offset ), $limit );
		}

		if ( ! $this->isPreloadEnabled( $options ) ) {
			return [];
		}

		return array_slice( $mediaIds, 0, $preloadLimit );
	}

	/**
	 * @param array $options Feed options payload.
	 */
	private function isPreloadEnabled( array $options ): bool {
		$settings = isset( $options['settings'] ) && is_array( $options['settings'] ) ? $options['settings'] : [];

		if ( ! array_key_exists( 'preloadMedia', $settings ) ) {
			return true;
		}

		return (bool) $settings['preloadMedia'];
	}
}
