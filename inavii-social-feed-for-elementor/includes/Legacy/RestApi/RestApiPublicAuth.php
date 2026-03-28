<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\RestApi;

use WP_REST_Request;

class RestApiPublicAuth {

	public static function isAuthorized( WP_REST_Request $request ): bool {
		$authToken = htmlspecialchars( $request->get_header( 'Inavii-SF-Auth-Token' ), ENT_QUOTES, 'UTF-8' );

		return $authToken === get_option( 'inavii_social_feed_public_auth_token' );
	}
}
