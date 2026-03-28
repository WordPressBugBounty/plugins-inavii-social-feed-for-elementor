<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Domain\Policy;

final class SourceMixModePolicy {
	public const MODE_BALANCED = 'balanced';
	public const MODE_OVERALL  = 'overall';

	public function resolve( int $feedId, array $sourceKeys, array $filters ): string {
		$mode = apply_filters(
			'inavii/social-feed/media/source_mix_mode',
			self::MODE_OVERALL,
			$feedId,
			$sourceKeys,
			$filters
		);

		if ( ! is_string( $mode ) ) {
			return self::MODE_OVERALL;
		}

		$mode = strtolower( trim( $mode ) );

		return $mode === self::MODE_BALANCED
			? self::MODE_BALANCED
			: self::MODE_OVERALL;
	}
}
