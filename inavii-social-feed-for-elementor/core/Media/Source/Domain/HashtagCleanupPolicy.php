<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Domain;

/**
 * Business rule for deleting hashtag source data.
 *
 * We can delete hashtag media/source only when:
 * - source row is valid,
 * - kind is hashtag,
 * - source is not pinned,
 * - no feed uses this source anymore.
 */
final class HashtagCleanupPolicy {
	private SourceCleanupPolicy $sourceCleanup;

	public function __construct( ?SourceCleanupPolicy $sourceCleanup = null ) {
		$this->sourceCleanup = $sourceCleanup instanceof SourceCleanupPolicy
			? $sourceCleanup
			: new SourceCleanupPolicy();
	}

	public function canDeleteSource( array $sourceRow, int $usedByFeedsCount ): bool {
		$kind = isset( $sourceRow['kind'] ) ? (string) $sourceRow['kind'] : '';
		if ( $kind !== Source::KIND_HASHTAG ) {
			return false;
		}

		return $this->sourceCleanup->canDeleteUnpinnedUnusedSource( $sourceRow, $usedByFeedsCount );
	}
}
