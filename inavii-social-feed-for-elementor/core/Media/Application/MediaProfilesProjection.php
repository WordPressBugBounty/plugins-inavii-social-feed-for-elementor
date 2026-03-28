<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application;

final class MediaProfilesProjection {
	/**
	 * @param array $items
	 *
	 * @return array
	 */
	public function project( array $items ): array {
		$media    = [];
		$profiles = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$profile = $this->resolveProfile( $item );
			$ref     = $profile['ref'];

			$profiles[ $ref ]   = $profile['data'];
			$item['profileRef'] = $ref;

			unset(
				$item['accountUsername'],
				$item['accountDisplayName'],
				$item['accountAvatarUrl'],
				$item['accountProfileUrl']
			);

			$media[] = $item;
		}

		return [
			'media'    => $media,
			'profiles' => $profiles,
		];
	}

	/**
	 * @param array $item
	 *
	 * @return array
	 */
	private function resolveProfile( array $item ): array {
		$accountHandle = $this->resolveAccountHandle( $item );
		$usernameBase  = $this->extractHandleBase( $accountHandle );
		$displayName   = $this->resolveDisplayName( $item, $usernameBase, $accountHandle );
		$profileUrl    = $this->resolveProfileUrl( $item, $accountHandle, $usernameBase );
		$avatarUrl     = $this->resolveAvatarUrl( $item );
		$ref           = $this->resolveProfileRef( $accountHandle, $usernameBase );

		return [
			'ref'  => $ref,
			'data' => [
				'accountUsername'    => $accountHandle,
				'accountDisplayName' => $displayName,
				'accountAvatarUrl'   => $avatarUrl,
				'accountProfileUrl'  => $profileUrl,
			],
		];
	}

	/**
	 * @param array $item
	 */
	private function resolveAccountHandle( array $item ): string {
		$rawHandle = '';
		if ( isset( $item['accountUsername'] ) ) {
			$rawHandle = trim( (string) $item['accountUsername'] );
		}
		if ( $rawHandle === '' && isset( $item['username'] ) ) {
			$rawHandle = trim( (string) $item['username'] );
		}

		if ( strpos( $rawHandle, '#' ) === 0 ) {
			$tag = ltrim( $rawHandle, '#' );
			if ( $tag !== '' ) {
				return '#' . $tag;
			}
		}

		$username = trim( ltrim( $rawHandle, '@' ) );
		if ( $username === '' ) {
			$username = 'instagram';
		}

		return '@' . $username;
	}

	private function extractHandleBase( string $handle ): string {
		return trim( ltrim( $handle, '@#' ) );
	}

	private function resolveProfileRef( string $handle, string $base ): string {
		$normalizedBase = strtolower( $base );
		if ( strpos( $handle, '#' ) === 0 ) {
			return 'tag:' . $normalizedBase;
		}

		return 'usr:' . $normalizedBase;
	}

	/**
	 * @param array $item
	 */
	private function resolveDisplayName( array $item, string $usernameBase, string $handle ): string {
		$name = isset( $item['accountDisplayName'] ) ? trim( (string) $item['accountDisplayName'] ) : '';
		if ( $name === '' ) {
			$name = strpos( $handle, '#' ) === 0 ? $handle : $usernameBase;
		}

		return $name;
	}

	/**
	 * @param array  $item
	 * @param string $handle
	 * @param string $usernameBase
	 *
	 * @return string
	 */
	private function resolveProfileUrl( array $item, string $handle, string $usernameBase ): string {
		$url = isset( $item['accountProfileUrl'] ) ? trim( (string) $item['accountProfileUrl'] ) : '';
		if ( $url === '' ) {
			if ( strpos( $handle, '#' ) === 0 ) {
				$url = 'https://www.instagram.com/explore/tags/' . rawurlencode( $usernameBase ) . '/';
			} else {
				$url = 'https://www.instagram.com/' . $usernameBase;
			}
		}

		return $url;
	}

	/**
	 * @param array $item
	 */
	private function resolveAvatarUrl( array $item ): string {
		return isset( $item['accountAvatarUrl'] ) ? trim( (string) $item['accountAvatarUrl'] ) : '';
	}
}
