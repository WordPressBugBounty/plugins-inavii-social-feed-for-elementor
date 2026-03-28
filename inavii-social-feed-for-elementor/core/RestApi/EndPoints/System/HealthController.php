<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi\EndPoints\System;

use Inavii\Instagram\Config\Troubleshooting\HealthDiagnostics;
use WP_REST_Request;
use WP_REST_Response;

final class HealthController {
	private HealthDiagnostics $diagnostics;

	public function __construct( HealthDiagnostics $diagnostics ) {
		$this->diagnostics = $diagnostics;
	}

	public function issues( WP_REST_Request $request ): WP_REST_Response {
		try {
			$freshParam = $request->get_param( 'fresh' );
			$freshFlag  = is_scalar( $freshParam ) ? (string) $freshParam : '';
			$useCache   = ! in_array( $freshFlag, [ '1', 'true', 'yes', 'on' ], true );

			$blockingParam = $request->get_param( 'blocking' );
			$blockingFlag  = is_scalar( $blockingParam ) ? (string) $blockingParam : '';
			$blockingOnly  = in_array( $blockingFlag, [ '1', 'true', 'yes', 'on' ], true );

			$issues = $this->diagnostics->issues( $useCache, $blockingOnly );

			return new WP_REST_Response(
				[
					'count'  => count( $issues ),
					'issues' => $issues,
				],
				200
			);
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
