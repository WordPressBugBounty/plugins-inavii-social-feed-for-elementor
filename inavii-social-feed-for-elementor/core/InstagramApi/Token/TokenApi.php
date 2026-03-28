<?php
declare(strict_types=1);

namespace Inavii\Instagram\InstagramApi\Token;

use Inavii\Instagram\InstagramApi\InstagramApiClient;

final class TokenApi {

	private InstagramApiClient $client;

	public function __construct( InstagramApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Refresh a long-lived Instagram token.
	 *
	 * @param string $accessToken
	 *
	 * @return array
	 */
	public function refreshInstagramToken( string $accessToken ): array {
		return $this->client->getJson(
			'https://graph.instagram.com/refresh_access_token',
			[
				'grant_type'   => 'ig_refresh_token',
				'access_token' => $accessToken,
			]
		);
	}
}
