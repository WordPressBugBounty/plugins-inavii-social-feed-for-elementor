<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Domain;

final class SourceCleanupPolicy {
	private const DEFAULT_DISABLED_DAYS = 7;

	public function canDeleteUnpinnedUnusedSource( array $sourceRow, int $usedByFeedsCount ): bool {
		$sourceId = isset( $sourceRow['id'] ) ? (int) $sourceRow['id'] : 0;
		if ( $sourceId <= 0 ) {
			return false;
		}

		$sourceKey = isset( $sourceRow['source_key'] ) ? trim( (string) $sourceRow['source_key'] ) : '';
		if ( $sourceKey === '' ) {
			return false;
		}

		$isPinned = isset( $sourceRow['is_pinned'] ) ? (int) $sourceRow['is_pinned'] : 0;
		if ( $isPinned === 1 ) {
			return false;
		}

		return $usedByFeedsCount <= 0;
	}

	public function disabledCleanupDays(): int {
		if ( function_exists( 'apply_filters' ) ) {
			$days = (int) apply_filters( 'inavii/social-feed/media/source/disabled_cleanup_days', self::DEFAULT_DISABLED_DAYS );
		} else {
			$days = self::DEFAULT_DISABLED_DAYS;
		}

		return $days > 0 ? $days : self::DEFAULT_DISABLED_DAYS;
	}
}
