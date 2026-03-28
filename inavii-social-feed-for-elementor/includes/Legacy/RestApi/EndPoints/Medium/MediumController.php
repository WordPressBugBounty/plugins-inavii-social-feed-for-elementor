<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Medium;

use Inavii\Instagram\MediumNew\Application\MediaFetchService;
use Inavii\Instagram\MediumNew\Application\MediaFinder;
use Inavii\Instagram\MediumNew\Domain\Source;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class MediumController {

	private MediaFetchService $fetch;
	private MediaFinder $finder;
	private ApiResponse $api;

	public function __construct( MediaFetchService $fetch, MediaFinder $finder, ApiResponse $api ) {
		$this->fetch  = $fetch;
		$this->finder = $finder;
		$this->api    = $api;
	}

	public function get( WP_REST_Request $request ): WP_REST_Response {
		try {
			$sourceKey = (int) $request->get_param( 'sourceKey' );
			$media     = $this->finder->bySourceKey( $sourceKey );

			return $this->api->response( $media );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	public function create( WP_REST_Request $request ): WP_REST_Response {
		try {
			$data = $request->get_params();

			// TODO

			// $source = Source::
			// $this->fetch->fetch()

			return $this->api->response(
				[
					[],
				]
			);
		} catch ( \InvalidArgumentException $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}
}
