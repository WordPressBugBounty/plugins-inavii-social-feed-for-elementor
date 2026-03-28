<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application\Hooks;

use Inavii\Instagram\Feed\Domain\Feed;
use Inavii\Instagram\Media\Source\Application\CleanupFeedSources;
use Inavii\Instagram\Media\Source\Application\SyncFeedSources;

final class FeedHooks {
	private SyncFeedSources $syncSources;
	private CleanupFeedSources $cleanupSources;

	public function __construct( SyncFeedSources $syncSources, CleanupFeedSources $cleanupSources ) {
		$this->syncSources    = $syncSources;
		$this->cleanupSources = $cleanupSources;
	}

	public function register(): void {
		add_action( 'inavii/social-feed/feed/created', [ $this, 'onFeedChanged' ], 10, 1 );
		add_action( 'inavii/social-feed/feed/updated', [ $this, 'onFeedChanged' ], 10, 1 );
		add_action( 'inavii/social-feed/feed/deleted', [ $this, 'onFeedDeleted' ], 10, 2 );
	}

	public function onFeedChanged( Feed $feed ): void {
		$this->syncSources->handle( $feed );
	}

	public function onFeedDeleted( int $feedId, ?Feed $feed = null ): void {
		$this->cleanupSources->handle( $feedId );
	}
}
