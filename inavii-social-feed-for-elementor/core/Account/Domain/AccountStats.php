<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain;

final class AccountStats {
	private int $mediaCount;
	private int $followersCount;
	private int $followsCount;

	public function __construct( int $mediaCount, int $followersCount, int $followsCount ) {
		$this->mediaCount     = $mediaCount;
		$this->followersCount = $followersCount;
		$this->followsCount   = $followsCount;
	}

	public static function fromPayload( array $payload ): self {
		return new self(
			(int) ( $payload['media_count'] ?? 0 ),
			(int) ( $payload['followers_count'] ?? 0 ),
			(int) ( $payload['follows_count'] ?? 0 )
		);
	}

	public function mediaCount(): int {
		return $this->mediaCount;
	}

	public function followersCount(): int {
		return $this->followersCount;
	}

	public function followsCount(): int {
		return $this->followsCount;
	}
}
