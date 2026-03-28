<?php
declare(strict_types=1);

namespace Inavii\Instagram\InstagramApi;

use Inavii\Instagram\InstagramApi\Http\HttpClient;

final class InstagramApiClient {

	private HttpClient $http;

	public function __construct( HttpClient $http ) {
		$this->http = $http;
	}

	/**
	 * @param string $url
	 * @param array $params
	 *
	 * @return array
	 */
	public function getJson( string $url, array $params = [] ): array {
		$response = $this->http->get( $url, $params );

		return $this->decodeResponse( $response->body(), $response->statusCode(), $response->message(), $response->isError() );
	}

	/**
	 * Use when the URL already includes query params (e.g., paging.next).
	 *
	 * @param string $url
	 *
	 * @return array
	 */
	public function getJsonUrl( string $url ): array {
		$response = $this->http->get( $url, [] );

		return $this->decodeResponse( $response->body(), $response->statusCode(), $response->message(), $response->isError() );
	}

	/**
	 * @param string $body
	 * @param int $status
	 * @param string $message
	 * @param bool $isError
	 *
	 * @return array
	 */
	private function decodeResponse( string $body, int $status, string $message, bool $isError ): array {
		if ( $body === '' ) {
			throw new InstagramApiException( 'Empty response from Instagram API', 0, '', $status, 0, [] );
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			throw new InstagramApiException(
				'Invalid JSON response from Instagram API',
				0,
				'',
				$status,
				0,
				[
					'body' => $body,
				]
			);
		}

		if ( isset( $data['error'] ) && is_array( $data['error'] ) ) {
			$err        = $data['error'];
			$errMessage = isset( $err['message'] ) ? (string) $err['message'] : 'Instagram API error';
			$errType    = isset( $err['type'] ) ? (string) $err['type'] : '';
			$errCode    = isset( $err['code'] ) ? (int) $err['code'] : 0;
			$errSub     = isset( $err['error_subcode'] ) ? (int) $err['error_subcode'] : 0;

			throw new InstagramApiException( $errMessage, $errCode, $errType, $status, $errSub, $data );
		}

		if ( $isError ) {
			$msg = $message !== '' ? $message : 'Instagram API HTTP error';
			throw new InstagramApiException( $msg, $status, 'HttpError', $status, 0, $data );
		}

		return $data;
	}
}
