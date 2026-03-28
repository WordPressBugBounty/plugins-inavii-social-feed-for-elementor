<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain\Policy;

final class TokenErrorClassifier {
	public function isTokenError( int $code, int $subcode, string $type, string $message ): bool {
		$type = strtolower( $type );

		if ( $code === 190 || $subcode === 463 || $subcode === 467 || $type === 'oauthexception' ) {
			return true;
		}

		return $this->isTokenErrorMessage( $message );
	}

	public function isTokenErrorMessage( string $message ): bool {
		$message  = strtolower( $message );
		$patterns = [
			'access token',
			'token expired',
			'session has expired',
			'invalid or expired',
			'invalid oauth',
			'permission',
		];

		foreach ( $patterns as $pattern ) {
			$pattern = strtolower( trim( (string) $pattern ) );
			if ( $pattern === '' ) {
				continue;
			}

			if ( strpos( $message, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
