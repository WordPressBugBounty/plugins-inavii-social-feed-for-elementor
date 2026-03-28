<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi;

final class PublicRequestKey {
	private const FEED_SCOPE = 'front-feed';
	private const CRON_SCOPE = 'cron-ping';

	public function createFeedKey( int $feedId ): string {
		if ( $feedId <= 0 ) {
			return '';
		}

		return $this->sign( self::FEED_SCOPE . ':' . $feedId );
	}

	public function verifyFeedKey( int $feedId, string $key ): bool {
		$key = trim( $key );
		if ( $feedId <= 0 || $key === '' ) {
			return false;
		}

		return hash_equals( $this->createFeedKey( $feedId ), $key );
	}

	public function createCronPingKey(): string {
		return $this->sign( self::CRON_SCOPE );
	}

	public function verifyCronPingKey( string $key ): bool {
		$key = trim( $key );
		if ( $key === '' ) {
			return false;
		}

		return hash_equals( $this->createCronPingKey(), $key );
	}

	private function sign( string $payload ): string {
		return hash_hmac( 'sha256', $payload, $this->secret() );
	}

	private function secret(): string {
		if ( function_exists( 'wp_salt' ) ) {
			$secret = (string) wp_salt( 'auth' );
			if ( $secret !== '' ) {
				return 'inavii-public-request:' . $secret;
			}
		}

		if ( defined( 'AUTH_KEY' ) && is_string( AUTH_KEY ) && AUTH_KEY !== '' ) {
			return 'inavii-public-request:' . AUTH_KEY;
		}

		return 'inavii-public-request:' . (string) get_site_url();
	}
}
