<?php
declare(strict_types=1);

namespace Inavii\Instagram\InstagramApi\Http;

final class HttpClient {

	public function get( string $url, array $params = [], int $timeout = 180 ): HttpResponse {
		$preparedUrl = $params !== [] ? add_query_arg( $params, $url ) : $url;

		$response = wp_remote_get(
			$preparedUrl,
			[
				'timeout' => $timeout,
			]
		);

		return new HttpResponse( $response );
	}
}
