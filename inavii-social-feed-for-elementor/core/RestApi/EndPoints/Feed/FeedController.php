<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi\EndPoints\Feed;

use Inavii\Instagram\Feed\Application\FeedService;
use Inavii\Instagram\Feed\Application\UseCase\PreviewFeedSources;
use Inavii\Instagram\Feed\Domain\Exceptions\FeedNotFoundException;
use Inavii\Instagram\Feed\Domain\FeedMediaFilters;
use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Feed\Domain\FeedSources;
use WP_REST_Request;
use WP_REST_Response;

final class FeedController {
	private FeedService $service;
	private PreviewFeedSources $preview;

	public function __construct( FeedService $service, PreviewFeedSources $preview ) {
		$this->service = $service;
		$this->preview = $preview;
	}

	public function all( WP_REST_Request $request ): WP_REST_Response {
		$request->get_method();

		try {
			return new WP_REST_Response( $this->service->all(), 200 );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function get( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );
		if ( $id <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid feed id.' ], 400 );
		}

		try {
			return new WP_REST_Response( $this->service->getForAdminApp( $id ), 200 );
		} catch ( FeedNotFoundException $e ) {
			return new WP_REST_Response( [ 'error' => 'Feed not found.' ], 404 );
		}
	}

	public function create( WP_REST_Request $request ): WP_REST_Response {
		$params   = $this->extract_payload( $request );
		$title    = $params['title'];
		$feedType = $params['feedType'];
		$feedMode = $params['feedMode'];

		if ( $feedType === '' ) {
			return new WP_REST_Response( [ 'error' => 'Missing feedType.' ], 400 );
		}

		try {
			$settings = FeedSettings::fromArray( $params['settings'] );
			$feed     = $this->service->create( $title, $feedType, $feedMode, $settings );

			return new WP_REST_Response( $this->service->getForAdminApp( $feed->id() ), 201 );
		} catch ( \InvalidArgumentException $e ) {
			return $this->invalidArgumentResponse( $e );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );
		if ( $id <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid feed id.' ], 400 );
		}

		try {
			$params   = $this->extract_payload( $request );
			$title    = $params['title'];
			$settings = FeedSettings::fromArray( $params['settings'] );
			$feedMode = $params['feedMode'];

			$this->service->updateSettings( $id, $title, $settings, $feedMode );
			return new WP_REST_Response( $this->service->getForAdminApp( $id ), 200 );
		} catch ( FeedNotFoundException $e ) {
			return new WP_REST_Response( [ 'error' => 'Feed not found.' ], 404 );
		} catch ( \InvalidArgumentException $e ) {
			return $this->invalidArgumentResponse( $e );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function delete( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );
		if ( $id <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid feed id.' ], 400 );
		}

		try {
			$this->service->delete( $id );
			return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
		} catch ( FeedNotFoundException $e ) {
			return new WP_REST_Response( [ 'error' => 'Feed not found.' ], 404 );
		} catch ( \InvalidArgumentException $e ) {
			return $this->invalidArgumentResponse( $e );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function clearCache( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );
		if ( $id <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid feed id.' ], 400 );
		}

		try {
			$this->service->clearCache( $id );
			return new WP_REST_Response( [ 'message' => 'Feed cache has been cleared' ], 200 );
		} catch ( FeedNotFoundException $e ) {
			return new WP_REST_Response( [ 'error' => 'Feed not found.' ], 404 );
		} catch ( \InvalidArgumentException $e ) {
			return $this->invalidArgumentResponse( $e );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function front( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );
		if ( $id <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid feed id.' ], 400 );
		}

		$limit  = (int) $request->get_param( 'limit' );
		$offset = (int) $request->get_param( 'offset' );

		if ( $limit <= 0 ) {
			$limit = 30;
		}
		if ( $offset < 0 ) {
			$offset = 0;
		}

		try {
			return new WP_REST_Response( $this->service->getForFrontApp( $id, $limit, $offset ), 200 );
		} catch ( FeedNotFoundException $e ) {
			return new WP_REST_Response( [ 'error' => 'Feed not found.' ], 404 );
		} catch ( \InvalidArgumentException $e ) {
			return $this->invalidArgumentResponse( $e );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function preview( WP_REST_Request $request ): WP_REST_Response {
		try {
			$data    = $request->get_param( 'data' );
			$payload = is_array( $data ) ? $data : $request->get_params();

			$sourceData = [];
			if ( isset( $payload['sources'] ) && is_array( $payload['sources'] ) ) {
				$sourceData = $payload['sources'];
			} elseif (
				isset( $payload['settings'] ) &&
				is_array( $payload['settings'] ) &&
				isset( $payload['settings']['source'] ) &&
				is_array( $payload['settings']['source'] )
			) {
				$sourceData = $payload['settings']['source'];
			}

			$sources = FeedSources::fromArray( $sourceData );

			$limit = isset( $payload['limit'] ) ? (int) $payload['limit'] : 0;
			if ( $limit <= 0 && isset( $payload['settings'] ) && is_array( $payload['settings'] ) ) {
				$design     = isset( $payload['settings']['design'] ) && is_array( $payload['settings']['design'] )
					? $payload['settings']['design']
					: [];
				$feedLayout = isset( $design['feedLayout'] ) && is_array( $design['feedLayout'] )
					? $design['feedLayout']
					: [];
				$limit      = isset( $feedLayout['numberOfPosts'] ) ? (int) $feedLayout['numberOfPosts'] : 0;
			}
			$offset  = isset( $payload['offset'] ) ? (int) $payload['offset'] : 0;
			$refresh = true;
			if ( array_key_exists( 'refresh', $payload ) ) {
				$refresh = filter_var( $payload['refresh'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
				$refresh = $refresh === null ? true : $refresh;
			}

			if ( $limit <= 0 ) {
				$limit = 30;
			}
			if ( $offset < 0 ) {
				$offset = 0;
			}

			$filtersInput = [];
			if ( isset( $payload['filters'] ) && is_array( $payload['filters'] ) ) {
				$filtersInput = $payload['filters'];
			} elseif (
				isset( $payload['settings'] ) &&
				is_array( $payload['settings'] ) &&
				isset( $payload['settings']['filters'] ) &&
				is_array( $payload['settings']['filters'] )
			) {
				$filtersInput = $payload['settings']['filters'];
			}

			$filters = FeedMediaFilters::fromArray( $filtersInput );

			$feedId = isset( $payload['id'] ) ? absint( $payload['id'] ) : 0;
			$result = $this->preview->handle( $sources, $limit, $offset, $refresh, $filters, $feedId );

			return new WP_REST_Response( $result, 200 );
		} catch ( \InvalidArgumentException $e ) {
			return $this->invalidArgumentResponse( $e );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	/**
	 * @param WP_REST_Request $request Incoming request payload.
	 *
	 * @return array{title:string,feedType:string,feedMode:string,settings:array<string,mixed>}
	 */
	private function extract_payload( WP_REST_Request $request ): array {
		$data    = $request->get_param( 'data' );
		$payload = is_array( $data ) ? $data : $request->get_params();

		$title    = isset( $payload['title'] ) ? sanitize_text_field( wp_unslash( (string) $payload['title'] ) ) : '';
		$feedType = isset( $payload['feedType'] ) ? sanitize_text_field( wp_unslash( (string) $payload['feedType'] ) ) : '';
		$feedMode = isset( $payload['feedMode'] ) ? sanitize_text_field( wp_unslash( (string) $payload['feedMode'] ) ) : '';
		$settings = isset( $payload['settings'] ) && is_array( $payload['settings'] ) ? $payload['settings'] : [];

		return [
			'title'    => $title,
			'feedType' => $feedType,
			'feedMode' => $feedMode,
			'settings' => $settings,
		];
	}

	private function invalidArgumentResponse( \InvalidArgumentException $e ): WP_REST_Response {
		return new WP_REST_Response( [ 'error' => $e->getMessage() ], 400 );
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
