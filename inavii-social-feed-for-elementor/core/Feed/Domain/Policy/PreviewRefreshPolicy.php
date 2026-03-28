<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Domain\Policy;

final class PreviewRefreshPolicy {
	private const DEFAULT_PREVIEW_REFRESH_TTL = 1800;

	public function ttlSeconds(): int {
		return self::DEFAULT_PREVIEW_REFRESH_TTL;
	}

	public function shouldRefresh( int $now, int $ttlSeconds, int $lastSyncAt = 0, int $lastSeenAt = 0 ): bool {
		if ( $ttlSeconds <= 0 ) {
			return true;
		}

		$referenceTimestamp = max( $lastSyncAt, $lastSeenAt );
		if ( $referenceTimestamp <= 0 ) {
			return true;
		}

		return ( $now - $referenceTimestamp ) >= $ttlSeconds;
	}
}
