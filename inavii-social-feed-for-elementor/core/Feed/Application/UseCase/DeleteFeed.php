<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Application\UseCase;

use Inavii\Instagram\Feed\Storage\FeedRepository;

final class DeleteFeed {
	private FeedRepository $feeds;

	public function __construct( FeedRepository $feeds ) {
		$this->feeds = $feeds;
	}

	public function handle( int $feedId ): void {
		if ( $feedId <= 0 ) {
			throw new \InvalidArgumentException( 'Feed id must be > 0.' );
		}

		$feed = $this->feeds->get( $feedId );
		$this->feeds->delete( $feedId );

		/**
		 * Fires after a feed has been deleted.
		 *
		 * @param int $feedId
		 * @param \Inavii\Instagram\Feed\Domain\Feed $feed
		 */
		do_action( 'inavii/social-feed/feed/deleted', $feedId, $feed );
		do_action( 'inavii/social-feed/front-index/delete', [ 'feedId' => $feedId ] );
	}
}
