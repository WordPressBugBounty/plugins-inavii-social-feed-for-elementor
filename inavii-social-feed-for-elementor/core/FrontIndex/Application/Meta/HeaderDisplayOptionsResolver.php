<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

final class HeaderDisplayOptionsResolver {
	/**
	 * @param array $design
	 */
	public function isDisabled( array $design ): bool {
		return isset( $design['headerEnabled'] ) && is_bool( $design['headerEnabled'] ) && $design['headerEnabled'] === false;
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
				'showProfilePicture',
				'showFullName',
				'showUsername',
				'showPostsCount',
				'showFollowersCount',
				'showFollowingCount',
				'showFollowButton',
			]
		);
	}

	/**
	 * @param array $buttonSettings
	 */
	public function normalizeFollowLabel( array $buttonSettings ): string {
		if ( isset( $buttonSettings['followLabel'] ) && is_string( $buttonSettings['followLabel'] ) ) {
			return trim( $buttonSettings['followLabel'] );
		}

		return '';
	}

	/**
	 * @param array $header
	 *
	 * @return array
	 */
	public function applyHashtagOverrides( array $header ): array {
		$header['showFullName']       = true;
		$header['showUsername']       = false;
		$header['showPostsCount']     = false;
		$header['showFollowersCount'] = false;
		$header['showFollowingCount'] = false;

		return $header;
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
