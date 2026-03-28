<?php
declare( strict_types=1 );

namespace Inavii\Instagram\InstagramApi\Media;

use Inavii\Instagram\InstagramApi\InstagramApiClient;

final class MediaPaginator {
	private InstagramApiClient $client;

	public function __construct( InstagramApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * @param string $url
	 * @param array $params
	 * @param int $limit
	 *
	 * @return array
	 */
	public function fetchAll( string $url, array $params, int $limit ): array {
		$items      = [];
		$itemsCount = 0;
		$nextUrl    = $url;
		$nextParams = $params;

		while ( $nextUrl && $itemsCount < $limit ) {
			$data = $nextParams !== null
				? $this->client->getJson( $nextUrl, $nextParams )
				: $this->client->getJsonUrl( $nextUrl );

			$batch = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : [];
			if ( $batch !== [] ) {
				$items = array_merge( $items, $batch );
				$itemsCount = count( $items );
			}

			$nextUrl    = isset( $data['paging']['next'] ) ? (string) $data['paging']['next'] : '';
			$nextParams = null;
		}

		if ( $itemsCount > $limit ) {
			return array_slice( $items, 0, $limit );
		}

		return $items;
	}
}
