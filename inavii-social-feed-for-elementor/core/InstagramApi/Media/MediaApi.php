<?php
declare( strict_types=1 );

namespace Inavii\Instagram\InstagramApi\Media;

final class MediaApi {
	private const DEFAULT_LIMIT = 50;

	private MediaPaginator $paginator;

	public function __construct( MediaPaginator $paginator ) {
		$this->paginator = $paginator;
	}

	/**
	 * @param string   $igAccountId instagram account id.
	 * @param string   $accessToken access token for the instagram account.
	 * @param int|null $limit maximum number of media items to fetch, if null it will use the default limit.
	 *
	 * @return array
	 */
	public function fetchAccountMedia( string $igAccountId, string $accessToken, ?int $limit = null ): array {
		$limit = $this->resolveLimit( $limit );

		return $this->paginator->fetchAll(
			"https://graph.facebook.com/v22.0/{$igAccountId}/media",
			[
				'access_token' => $accessToken,
				'limit'        => $limit,
				'fields'       => $this->fields(),
			],
			$limit
		);
	}

	/**
	 * @param string   $accessToken access token for the instagram account.
	 * @param int|null $limit maximum number of media items to fetch, if null it will use the default limit.
	 *
	 * @return array
	 */
	public function fetchPersonalMedia( string $accessToken, ?int $limit = null ): array {
		$limit = $this->resolveLimit( $limit );

		return $this->paginator->fetchAll(
			'https://graph.instagram.com/v22.0/me/media',
			[
				'access_token' => $accessToken,
				'limit'        => $limit,
				'fields'       => $this->fields(),
			],
			$limit
		);
	}

	private function resolveLimit( ?int $limit ): int {
		$limit = $limit ?? self::DEFAULT_LIMIT;

		$filterLimit = (int) apply_filters( 'inavii/social-feed/media/fetch_limit', $limit );
		if ( $filterLimit > 0 ) {
			return $filterLimit;
		}

		return $limit;
	}

	private function fields(): string {
		return 'id,caption,like_count,comments_count,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp,username,children{id,media_type,media_url,thumbnail_url,permalink}';
	}
}
