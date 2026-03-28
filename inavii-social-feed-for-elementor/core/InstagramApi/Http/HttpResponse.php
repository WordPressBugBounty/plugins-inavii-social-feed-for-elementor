<?php
declare(strict_types=1);

namespace Inavii\Instagram\InstagramApi\Http;

final class HttpResponse {

	/** @var mixed */
	private $response;

	/**
	 * @param mixed $response
	 */
	public function __construct( $response ) {
		$this->response = $response;
	}

	public function isError(): bool {
		if ( is_wp_error( $this->response ) ) {
			return true;
		}

		$code = $this->statusCode();

		return $code < 200 || $code >= 300;
	}

	public function statusCode(): int {
		if ( is_wp_error( $this->response ) ) {
			return 0;
		}

		return (int) wp_remote_retrieve_response_code( $this->response );
	}

	public function message(): string {
		if ( is_wp_error( $this->response ) ) {
			/** @var \WP_Error $err */
			$err = $this->response;
			return (string) $err->get_error_message();
		}

		return (string) wp_remote_retrieve_response_message( $this->response );
	}

	public function body(): string {
		if ( is_wp_error( $this->response ) ) {
			return '';
		}

		return (string) wp_remote_retrieve_body( $this->response );
	}
}
