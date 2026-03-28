<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi\EndPoints\Front;

use Inavii\Instagram\Front\Render;
use WP_REST_Request;
use WP_REST_Response;

final class FrontController {
	private const DEFAULT_MEDIA_LIMIT = 20;

	private Render $render;

	public function __construct( Render $render ) {
		$this->render = $render;
	}

	public function payload( WP_REST_Request $request ): WP_REST_Response {
		$feedId = absint( $request->get_param( 'id' ) );
		if ( $feedId <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid feed id.' ], 400 );
		}

		$limit  = (int) $request->get_param( 'limit' );
		$offset = (int) $request->get_param( 'offset' );
		if ( $limit < 0 ) {
			$limit = 0;
		}
		if ( $offset < 0 ) {
			$offset = 0;
		}

		try {
			$payload = $this->render->payload( $feedId, $limit, $offset );
			if ( $payload === [] ) {
				return new WP_REST_Response( [ 'error' => 'Feed not found.' ], 404 );
			}

			return new WP_REST_Response( $payload, 200 );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function media( WP_REST_Request $request ): WP_REST_Response {
		$feedId = absint( $request->get_param( 'id' ) );
		if ( $feedId <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid feed id.' ], 400 );
		}

		$limit  = (int) $request->get_param( 'limit' );
		$offset = (int) $request->get_param( 'offset' );

		if ( $limit <= 0 ) {
			$limit = self::DEFAULT_MEDIA_LIMIT;
		}
		if ( $offset < 0 ) {
			$offset = 0;
		}

		try {
			$payload = $this->render->media( $feedId, $limit, $offset );
			if ( $payload === [] ) {
				return new WP_REST_Response( [ 'error' => 'Feed not found.' ], 404 );
			}

			return new WP_REST_Response( $payload, 200 );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	private function unexpectedErrorResponse( \Throwable $e ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'error'   => 'Unexpected error',
				'message' => $e->getMessage(),
			],
			500
		);
	}
}
