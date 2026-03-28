<?php
declare(strict_types=1);

namespace Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Account;

use Inavii\Instagram\Account\Application\AccountService;
use Inavii\Instagram\InstagramApi\InstagramApiException;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

final class AccessTokenController {

	private AccountService $accounts;
	private ApiResponse $api;

	public function __construct( AccountService $accounts, ApiResponse $api ) {
		$this->accounts = $accounts;
		$this->api      = $api;
	}

	public function connect( WP_REST_Request $request ): WP_REST_Response {
		$accessToken = $request->get_param( 'accessToken' );
		$userId      = $request->get_param( 'userId' );

		$accessToken = is_string( $accessToken ) ? sanitize_text_field( wp_unslash( $accessToken ) ) : '';

		$accessToken = preg_replace( '/\s+/', '', $accessToken );
		$userId      = is_string( $userId ) ? sanitize_text_field( wp_unslash( $userId ) ) : '';

		if ( $accessToken === '' ) {
			return new WP_REST_Response( [ 'error' => 'The access token is required.' ], 400 );
		}

		try {
			$account = $this->accounts->connect( $accessToken, 0, (string) $userId );

			// 3) wyjście (kanoniczne)
				$username = $account->username();
				$name     = $username !== '' ? $username : $account->name();

				return $this->api->response(
					[
						'wpAccountID' => $account->id(),
						'name'        => $name,
					]
				);
		} catch ( InstagramApiException $e ) {
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
			// wszystko inne – nie wyciekamy szczegółów
			return new WP_REST_Response(
				[
					'error'   => 'Unexpected error',
					'message' => 'Account connection failed.',
				],
				500
			);
		}
	}
}
