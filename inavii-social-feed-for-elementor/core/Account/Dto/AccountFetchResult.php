<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Dto;

use Inavii\Instagram\Account\Domain\AccountSnapshot;
use Inavii\Instagram\Account\Domain\AccountStats;

final class AccountFetchResult {
	/** @var array */
	private array $payload;

	/** @param array $payload */
	public function __construct( array $payload ) {
		$this->payload = $payload;
	}

	/** @return array */
	public function payload(): array {
		return $this->payload;
	}

	public function stats(): AccountStats {
		return AccountStats::fromPayload( $this->payload );
	}

	public function toSnapshot( string $accessToken, int $tokenExpires, string $connectType, string $defaultType ): AccountSnapshot {
		return AccountSnapshot::fromApiPayload(
			$this->payload,
			$accessToken,
			$tokenExpires,
			$connectType,
			$defaultType,
			time()
		);
	}
}
