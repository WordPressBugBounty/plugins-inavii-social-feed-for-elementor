<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain\Policy;

final class ConnectAccountPolicy {
	public const DEFAULT_FACEBOOK_EXPIRES_SECONDS = 60 * 86400;
	public const DEFAULT_INSTAGRAM_EXPIRES_SECONDS = 60 * 86400;

	public function requireBusinessId( string $businessId ): string {
		$value = trim( $businessId );

		if ( $value === '' ) {
			throw new \InvalidArgumentException( 'Missing businessId for Facebook connection' );
		}

		return $value;
	}

	public function resolveFacebookTokenExpires( int $tokenExpires, int $now ): int {
		if ( $tokenExpires > 0 ) {
			return $tokenExpires;
		}

		return $now + self::DEFAULT_FACEBOOK_EXPIRES_SECONDS;
	}

	public function resolveInstagramTokenExpires( int $tokenExpires, int $now ): int {
		if ( $tokenExpires > 0 ) {
			return $tokenExpires;
		}

		return $now + self::DEFAULT_INSTAGRAM_EXPIRES_SECONDS;
	}
}
