<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Token;

final class AccessTokenCipher {
	private const METHOD = 'aes-256-ctr';

	/**
	 * @return array{0:string,1:string} [encrypted, iv]
	 */
	public function encrypt( string $value ): array {
		$keySalt = $this->wpKeySalt();
		if ( $value === '' || ! $this->canUseOpenSsl() || $keySalt === null ) {
			return [ $value, '' ];
		}

		$ivLength = openssl_cipher_iv_length( self::METHOD );
		$iv       = $this->randomBytes( $ivLength );
		if ( $iv === '' ) {
			return [ $value, '' ];
		}

		$salted    = $value . $keySalt['salt'];
		$encrypted = openssl_encrypt( $salted, self::METHOD, $keySalt['key'], 0, $iv );
		if ( ! is_string( $encrypted ) || $encrypted === '' ) {
			return [ $value, '' ];
		}

		return [ $encrypted, base64_encode( $iv ) ];
	}

	public function decrypt( string $encryptedOrRaw, string $ivEncoded ): string {
		if ( $encryptedOrRaw === '' ) {
			return $encryptedOrRaw;
		}

		if ( $ivEncoded === '' ) {
			return $encryptedOrRaw;
		}

		if ( ! $this->canUseOpenSsl() ) {
			return '';
		}

		$iv = base64_decode( $ivEncoded, true );
		if ( ! is_string( $iv ) || $iv === '' ) {
			return '';
		}

		$keySalt = $this->wpKeySalt();
		if ( $keySalt === null ) {
			return '';
		}

		$decrypted = openssl_decrypt( $encryptedOrRaw, self::METHOD, $keySalt['key'], 0, $iv );
		if ( ! is_string( $decrypted ) || $decrypted === '' ) {
			return '';
		}

		$salt = $keySalt['salt'];
		if ( substr( $decrypted, - strlen( $salt ) ) !== $salt ) {
			return '';
		}

		return substr( $decrypted, 0, - strlen( $salt ) );
	}

	/**
	 * @return array{key:string,salt:string}|null
	 */
	private function wpKeySalt(): ?array {
		if ( function_exists( 'wp_salt' ) ) {
			$secret = (string) wp_salt( 'logged_in' );
			if ( $secret !== '' ) {
				return [
					'key'  => $secret,
					'salt' => $secret,
				];
			}
		}

		$key  = ( defined( 'LOGGED_IN_KEY' ) && LOGGED_IN_KEY !== '' ) ? (string) LOGGED_IN_KEY : '';
		$salt = ( defined( 'LOGGED_IN_SALT' ) && LOGGED_IN_SALT !== '' ) ? (string) LOGGED_IN_SALT : '';
		if ( $key === '' || $salt === '' ) {
			return null;
		}

		return [
			'key'  => $key,
			'salt' => $salt,
		];
	}

	private function canUseOpenSsl(): bool {
		return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' );
	}

	private function randomBytes( int $length ): string {
		if ( $length <= 0 ) {
			return '';
		}

		try {
			return random_bytes( $length );
		} catch ( \Throwable $e ) {
			return '';
		}
	}
}
