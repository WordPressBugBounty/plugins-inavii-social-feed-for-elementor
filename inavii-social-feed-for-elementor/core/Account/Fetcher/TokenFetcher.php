<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Fetcher;

use Inavii\Instagram\Account\Dto\TokenRefreshResult;
use Inavii\Instagram\InstagramApi\Token\TokenApi;

final class TokenFetcher {
	private TokenApi $api;

	public function __construct( TokenApi $api ) {
		$this->api = $api;
	}

	public function refreshInstagramToken( string $accessToken ): TokenRefreshResult {
		$response = $this->api->refreshInstagramToken( $accessToken );

		return new TokenRefreshResult(
			isset( $response['access_token'] ) ? (string) $response['access_token'] : '',
			isset( $response['expires_in'] ) ? (int) $response['expires_in'] : 0
		);
	}
}
