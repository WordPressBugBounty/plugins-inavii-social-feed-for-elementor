<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Domain;

use Inavii\Instagram\Media\Source\Domain\Source;

final class MediaRetentionPolicy {
	private const SECONDS_PER_DAY      = 86400;
	private const DEFAULT_DAYS_ACCOUNT = 14;
	private const DEFAULT_DAYS_TAGGED  = 14;
	private const DEFAULT_DAYS_HASHTAG = 3;

	public function resolveDays( string $kind, string $sourceKey ): int {
		$kind = trim( $kind );
		if ( $kind === Source::KIND_HASHTAG ) {
			$days = self::DEFAULT_DAYS_HASHTAG;
		} elseif ( $kind === Source::KIND_TAGGED ) {
			$days = self::DEFAULT_DAYS_TAGGED;
		} else {
			$days = self::DEFAULT_DAYS_ACCOUNT;
		}

		if ( function_exists( 'apply_filters' ) ) {
			return (int) apply_filters( 'inavii/social-feed/media/retention_days', $days, $kind, $sourceKey );
		}

		return $days;
	}

	public function shouldCleanupSource( string $lastSuccessAt, int $days, int $now ): bool {
		if ( $days <= 0 ) {
			return false;
		}

		$lastSuccessTimestamp = strtotime( $lastSuccessAt );
		if ( $lastSuccessTimestamp === false ) {
			return false;
		}

		$cutoffTimestamp = $this->cutoffTimestamp( $days, $now );

		// Skip cleanup if source itself has not been synced recently enough.
		return $lastSuccessTimestamp >= $cutoffTimestamp;
	}

	public function cutoffDateTime( int $days, int $now ): string {
		return gmdate( 'Y-m-d H:i:s', $this->cutoffTimestamp( $days, $now ) );
	}

	private function cutoffTimestamp( int $days, int $now ): int {
		return $now - ( $days * self::SECONDS_PER_DAY );
	}
}
