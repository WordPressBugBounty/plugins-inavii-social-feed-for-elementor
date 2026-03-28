<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\RestApi;

class RestApiPublicAuthToken {

	/**
	 * Retrieves or generates a new token.
	 *
	 * @return string The token.
	 */
	public static function get(): string {
		$token = get_option( 'inavii_social_feed_public_auth_token' );

		if ( empty( $token ) ) {
			$token = sha1( (string) rand( 20, 30 ) );
			update_option( 'inavii_social_feed_public_auth_token', $token );
		}

		return $token;
	}
}
