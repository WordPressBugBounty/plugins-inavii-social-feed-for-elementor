<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Storage;

final class GlobalFeedRepository {
	private const OPTION_ACTIVE_FEED_ID = 'inavii_social_feed_global_offcanvas_feed_id';

	public function getActiveFeedId(): int {
		$value = get_option( self::OPTION_ACTIVE_FEED_ID, 0 );
		$id    = is_numeric( $value ) ? (int) $value : 0;

		return $id > 0 ? $id : 0;
	}

	public function setActiveFeedId( int $feedId ): void {
		$feedId = $feedId > 0 ? $feedId : 0;
		update_option( self::OPTION_ACTIVE_FEED_ID, $feedId, true );
	}

	public function clear(): void {
		$this->setActiveFeedId( 0 );
	}

	public function isInitialized(): bool {
		return get_option( self::OPTION_ACTIVE_FEED_ID, null ) !== null;
	}
}
