<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Domain;

final class MissingMediaCleanupPolicy {
	private const MODE_FAST = 'fast';
	private const MODE_OFF  = 'off';

	private const DEFAULT_DELETE_LIMIT        = 500;
	private const DEFAULT_TIME_BUDGET_SECONDS = 2.0;

	public function mode( string $sourceKind, string $sourceKey ): string {
		$mode = self::MODE_FAST;

		if ( function_exists( 'apply_filters' ) ) {
			$mode = (string) apply_filters(
				'inavii/social-feed/media/missing_cleanup_mode',
				$mode,
				$sourceKind,
				$sourceKey
			);
		}

		$mode = strtolower( trim( $mode ) );

		if ( in_array( $mode, [ '', '0', 'false', 'off', 'none', 'disabled' ], true ) ) {
			return self::MODE_OFF;
		}

		return self::MODE_FAST;
	}

	public function enabled( string $sourceKind, string $sourceKey ): bool {
		return $this->mode( $sourceKind, $sourceKey ) === self::MODE_FAST;
	}

	public function deleteLimit( string $sourceKind, string $sourceKey ): int {
		$limit = self::DEFAULT_DELETE_LIMIT;

		if ( function_exists( 'apply_filters' ) ) {
			$limit = (int) apply_filters(
				'inavii/social-feed/media/missing_cleanup_limit',
				$limit,
				$sourceKind,
				$sourceKey
			);
		}

		if ( $limit < 1 ) {
			return 1;
		}

		if ( $limit > 5000 ) {
			return 5000;
		}

		return $limit;
	}

	public function timeBudgetSeconds( string $sourceKind, string $sourceKey ): float {
		$seconds = self::DEFAULT_TIME_BUDGET_SECONDS;

		if ( function_exists( 'apply_filters' ) ) {
			$seconds = (float) apply_filters(
				'inavii/social-feed/media/missing_cleanup_time_budget',
				$seconds,
				$sourceKind,
				$sourceKey
			);
		}

		if ( $seconds < 0.2 ) {
			return 0.2;
		}

		if ( $seconds > 20.0 ) {
			return 20.0;
		}

		return $seconds;
	}
}
