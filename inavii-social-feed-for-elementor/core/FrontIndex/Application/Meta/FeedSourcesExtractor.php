<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

final class FeedSourcesExtractor {
	/**
	 * @param array $feed
	 *
	 * @return array
	 */
	public function extractSources( array $feed ): array {
		if ( isset( $feed['sources'] ) && is_array( $feed['sources'] ) ) {
			return $feed['sources'];
		}

		$settings = isset( $feed['settings'] ) && is_array( $feed['settings'] ) ? $feed['settings'] : [];
		if ( isset( $settings['source'] ) && is_array( $settings['source'] ) ) {
			return $settings['source'];
		}

		return [];
	}

	/**
	 * @param array $sources
	 *
	 * @return string[]
	 */
	public function extractHashtags( array $sources ): array {
		$raw = isset( $sources['hashtags'] ) && is_array( $sources['hashtags'] ) ? $sources['hashtags'] : [];
		$out = [];

		foreach ( $raw as $tag ) {
			$tag = strtolower( ltrim( trim( (string) $tag ), '#' ) );
			if ( $tag === '' ) {
				continue;
			}

			$out[] = $tag;
		}

		return array_values( array_unique( $out ) );
	}
}
