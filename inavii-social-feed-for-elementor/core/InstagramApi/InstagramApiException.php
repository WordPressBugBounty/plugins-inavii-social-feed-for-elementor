<?php
declare( strict_types=1 );

namespace Inavii\Instagram\InstagramApi;

use RuntimeException;
use Throwable;

final class InstagramApiException extends RuntimeException {
	private string $type;
	private int $httpStatus;
	private int $subcode;
	/** @var array */
	private array $raw;

	/**
	 * @param string $message
	 * @param int $code
	 * @param string $type
	 * @param int $httpStatus
	 * @param int $subcode
	 * @param array $raw
	 * @param Throwable|null $previous
	 */
	public function __construct(
		string $message,
		int $code = 0,
		string $type = '',
		int $httpStatus = 0,
		int $subcode = 0,
		array $raw = [],
		Throwable $previous = null
	) {
		$this->type       = $type;
		$this->httpStatus = $httpStatus;
		$this->subcode    = $subcode;
		$this->raw        = $raw;

		parent::__construct( $message, $code, $previous );
	}

	public function type(): string {
		return $this->type;
	}

	public function httpStatus(): int {
		return $this->httpStatus;
	}

	public function subcode(): int {
		return $this->subcode;
	}

	/**
	 * @return array
	 */
	public function raw(): array {
		return $this->raw;
	}

	public function requiresReconnect(): bool {
		$code    = (int) $this->getCode();
		$subcode = $this->subcode();
		$type    = strtolower( $this->type() );
		$message = strtolower( $this->getMessage() );

		if ( $code === 190 || $code === 10 || $code === 200 || $subcode === 463 || $subcode === 467 || $type === 'oauthexception' ) {
			return true;
		}

		return strpos( $message, 'access token' ) !== false
				|| strpos( $message, 'token expired' ) !== false
				|| strpos( $message, 'session has expired' ) !== false
				|| strpos( $message, 'invalid oauth' ) !== false
				|| strpos( $message, 'invalid or expired' ) !== false;
	}
}
