<?php
declare(strict_types=1);

namespace Inavii\Instagram\InstagramApi\Account;

use Inavii\Instagram\InstagramApi\InstagramApiClient;

final class AccountApi {

	private InstagramApiClient $client;

	public function __construct( InstagramApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * @return array
	 */
	public function fetchPersonal( string $accessToken ): array {
		return $this->client->getJson(
			'https://graph.instagram.com/v22.0/me',
			[
				'fields'       => 'id,username,media_count,account_type,profile_picture_url,biography,followers_count,follows_count',
				'access_token' => $accessToken,
			]
		);
	}

	/**
	 * @return array
	 */
	public function fetchBusiness( string $igAccountId, string $accessToken ): array {
		return $this->client->getJson(
			"https://graph.facebook.com/v22.0/{$igAccountId}",
			[
				'fields'       => 'id,name,username,profile_picture_url,media_count,biography,followers_count,follows_count',
				'access_token' => $accessToken,
			]
		);
	}
}
