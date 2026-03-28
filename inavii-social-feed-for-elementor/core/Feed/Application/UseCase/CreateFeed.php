<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Application\UseCase;

use Inavii\Instagram\Feed\Domain\Feed;
use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;
use Inavii\Instagram\Feed\Storage\FeedRepository;

final class CreateFeed {
	private FeedRepository $feeds;
	private AutoFeedTitleGenerator $titles;
	private ProFeaturesPolicy $proFeatures;

	public function __construct( FeedRepository $feeds, AutoFeedTitleGenerator $titles, ProFeaturesPolicy $proFeatures ) {
		$this->feeds       = $feeds;
		$this->titles      = $titles;
		$this->proFeatures = $proFeatures;
	}

	public function handle( string $title, string $feedType, string $feedMode, FeedSettings $settings ): Feed {
		$title = $this->titles->resolveForCreate( $title, $settings );

		$feedType = trim( $feedType );
		if ( $feedType === '' ) {
			$feedType = FeedRepository::DEFAULT_FEED_TYPE;
		}

		$feedMode = $this->proFeatures->sanitizeFeedMode( $feedMode );

		$newFeedId = $this->feeds->create( $title );
		$feed      = new Feed( $newFeedId, $title, $feedType, $feedMode, $settings );
		$this->feeds->save( $feed );

		/**
		 * Fires after a feed has been created.
		 */
		do_action( 'inavii/social-feed/feed/created', $feed );
		do_action( 'inavii/social-feed/front-index/rebuild', [ 'feedId' => $feed->id() ] );

		return $feed;
	}
}
