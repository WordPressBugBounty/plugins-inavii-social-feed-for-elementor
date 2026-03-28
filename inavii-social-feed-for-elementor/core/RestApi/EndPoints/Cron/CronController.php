<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi\EndPoints\Cron;

use Inavii\Instagram\Cron\CronFallback;
use WP_REST_Request;
use WP_REST_Response;

final class CronController {
	private CronFallback $fallback;

	public function __construct( CronFallback $fallback ) {
		$this->fallback = $fallback;
	}

	public function ping( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->fallback->handlePing( $request );
		} catch ( \Throwable $e ) {
			return new WP_REST_Response(
				[
					'error'   => 'Unexpected error',
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}
}
