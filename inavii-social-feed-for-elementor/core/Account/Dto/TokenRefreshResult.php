<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Dto;

final class TokenRefreshResult {
	private string $accessToken;
	private int $expiresIn;

	public function __construct( string $accessToken, int $expiresIn ) {
		$this->accessToken = $accessToken;
		$this->expiresIn   = $expiresIn;
	}

	public function accessToken(): string {
		return $this->accessToken;
	}

	public function expiresIn(): int {
		return $this->expiresIn;
	}

	public function hasToken(): bool {
		return $this->accessToken !== '' && $this->expiresIn > 0;
	}
}
