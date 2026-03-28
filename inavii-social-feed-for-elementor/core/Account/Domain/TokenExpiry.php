<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain;

final class TokenExpiry {
	public static function normalize( int $value, int $now ): int {
		if ( $value <= 0 ) {
			return 0;
		}

		if ( self::isUnixTimestamp( $value ) ) {
			return $value;
		}

		return $now + $value;
	}

	private static function isUnixTimestamp( int $value ): bool {
		return $value > 1000000000;
	}
}
