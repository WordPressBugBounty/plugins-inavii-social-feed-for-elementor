<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\Account\Application\AccountService;
use Inavii\Instagram\InstagramApi\InstagramApiException;
use Inavii\Instagram\Logger\Logger;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

final class AccountController {
	private AccountService $accounts;
	private ApiResponse $api;

	public function __construct(
		AccountService $accounts,
		ApiResponse $api
	) {
		$this->accounts = $accounts;
		$this->api      = $api;
	}

	public function connect( WP_REST_Request $request ): WP_REST_Response {
		try {
			[ $accessToken, $tokenExpires, $businessId ] = $this->extractConnectParams( $request );
			if ( $accessToken === '' ) {
				return new WP_REST_Response( [ 'error' => 'Invalid params' ], 400 );
			}

			$account = $this->accounts->connect( $accessToken, $tokenExpires, $businessId );

			return $this->api->response( $this->accounts->api()->map( $account ) );
		} catch ( InstagramApiException $e ) {
			Logger::error(
				'api/accounts',
				'Instagram API error while connecting account',
				[
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				]
			);
			return new WP_REST_Response(
				[
					'error'   => 'OAuth error',
					'message' => $e->getMessage(),
				],
				401
			);
		} catch ( \InvalidArgumentException $e ) {
			return new WP_REST_Response( [ 'error' => $e->getMessage() ], 400 );
		} catch ( \Throwable $e ) {
			Logger::error(
				'api/accounts',
				'Unexpected error while connecting account',
				[
					'message' => $e->getMessage(),
				]
			);
			return new WP_REST_Response(
				[
					'error'   => 'Unexpected error',
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * @return array{string,int,string}
	 */
	private function extractConnectParams( WP_REST_Request $request ): array {
		$data    = $request->get_param( 'data' );
		$payload = is_array( $data ) ? $data : null;

		$accessToken  = $payload !== null ? ( $payload['accessToken'] ?? null ) : $request->get_param( 'accessToken' );
		$tokenExpires = $payload !== null ? ( $payload['tokenExpires'] ?? null ) : $request->get_param( 'tokenExpires' );
		$businessId   = $payload !== null
			? ( $payload['userID'] ?? ( $payload['userId'] ?? ( $payload['businessId'] ?? null ) ) )
			: ( $request->get_param( 'userID' ) ?? ( $request->get_param( 'userId' ) ?? $request->get_param( 'businessId' ) ) );

		$accessToken  = is_string( $accessToken ) ? sanitize_text_field( wp_unslash( $accessToken ) ) : '';
		$tokenExpires = is_numeric( $tokenExpires ) ? absint( $tokenExpires ) : 0;
		$businessId   = is_string( $businessId ) ? sanitize_text_field( wp_unslash( $businessId ) ) : '';

		return [ $accessToken, $tokenExpires, $businessId ];
	}

	public function delete( WP_REST_Request $request ): WP_REST_Response {
		$accountId = $request->get_param( 'id' );
		$accountId = is_numeric( $accountId ) ? (int) $accountId : 0;

		if ( $accountId <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid account id' ], 400 );
		}

		try {
			$this->accounts->delete( $accountId );

			return $this->api->response( [ 'id' => $accountId ] );
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

	public function all(): WP_REST_Response {
		try {
			return $this->api->response( $this->accounts->api()->getAll() );
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

	public function get( WP_REST_Request $request ): WP_REST_Response {
		$accountId = $request->get_param( 'id' );
		$accountId = is_numeric( $accountId ) ? (int) $accountId : 0;

		if ( $accountId <= 0 ) {
			return new WP_REST_Response( [ 'error' => 'Invalid account id' ], 400 );
		}

		try {
			return $this->api->response( $this->accounts->api()->get( $accountId ) );
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
