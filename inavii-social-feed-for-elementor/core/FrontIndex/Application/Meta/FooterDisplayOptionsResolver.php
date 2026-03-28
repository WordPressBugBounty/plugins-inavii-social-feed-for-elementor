<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

final class FooterDisplayOptionsResolver {
	/**
	 * @param array $design
	 */
	public function isDisabled( array $design ): bool {
		return isset( $design['footerEnabled'] ) && is_bool( $design['footerEnabled'] ) && $design['footerEnabled'] === false;
	}

	/**
	 * @param array $elements
	 *
	 * @return array
	 */
	public function normalizeVisibility( array $elements ): array {
		return $this->filterBooleanKeys(
			$elements,
			[
				'showLoadMore',
				'showFollow',
			]
		);
	}

	/**
	 * @param array $buttonSettings
	 *
	 * @return array
	 */
	public function normalizeLabels( array $buttonSettings ): array {
		$out = [];

		if ( isset( $buttonSettings['loadMoreLabel'] ) && is_string( $buttonSettings['loadMoreLabel'] ) ) {
			$label = trim( $buttonSettings['loadMoreLabel'] );
			if ( $label !== '' ) {
				$out['loadMoreLabel'] = $label;
			}
		}

		if ( isset( $buttonSettings['followLabel'] ) && is_string( $buttonSettings['followLabel'] ) ) {
			$label = trim( $buttonSettings['followLabel'] );
			if ( $label !== '' ) {
				$out['followLabel'] = $label;
			}
		}

		return $out;
	}

	/**
	 * @param array $footer
	 */
	public function isFullyDisabled( array $footer ): bool {
		if ( array_key_exists( 'showLoadMore', $footer ) && array_key_exists( 'showFollow', $footer ) ) {
			return ! ( (bool) $footer['showLoadMore'] || (bool) $footer['showFollow'] );
		}

		return false;
	}

	/**
	 * @param array    $input
	 * @param string[] $keys
	 *
	 * @return array
	 */
	private function filterBooleanKeys( array $input, array $keys ): array {
		$out = [];

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$out[ $key ] = (bool) $input[ $key ];
			}
		}

		return $out;
	}
}
