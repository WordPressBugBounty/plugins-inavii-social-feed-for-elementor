<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Application\UseCase;

use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Feed\Domain\Exceptions\FeedNotFoundException;
use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;
use Inavii\Instagram\Feed\Storage\FeedRepository;

final class UpdateFeedSettings {

	private FeedRepository $feeds;
	private ProFeaturesPolicy $proFeatures;

	public function __construct( FeedRepository $feeds, ProFeaturesPolicy $proFeatures ) {
		$this->feeds       = $feeds;
		$this->proFeatures = $proFeatures;
	}

	/**
	 * @param int          $feedId
	 * @param string       $title
	 * @param FeedSettings $settings
	 * @param string       $feedMode
	 *
	 * @return void
	 *
	 * @throws FeedNotFoundException If feed with given id does not exist.
	 *
	 * @throws \InvalidArgumentException If feed id is invalid or title is empty.
	 */
	public function handle( int $feedId, string $title, FeedSettings $settings, string $feedMode ): void {
		if ( $feedId <= 0 ) {
			throw new \InvalidArgumentException( 'Feed id must be > 0.' );
		}

		$feed  = $this->feeds->get( $feedId );
		$title = trim( $title );
		if ( $title !== '' ) {
			$feed->rename( $title );
		}
		$feed->replaceSettings( $settings );
		$feedMode = $this->proFeatures->sanitizeFeedMode( $feedMode );
		if ( $feedMode !== '' ) {
			$feed->updateFeedMode( $feedMode );
		}
		$this->feeds->save( $feed );

		/**
		 * Fires after feed settings have been updated.
		 */
		do_action( 'inavii/social-feed/feed/updated', $feed );
		do_action( 'inavii/social-feed/front-index/rebuild', [ 'feedId' => $feed->id() ] );
	}
}
