<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Application;

use Inavii\Instagram\Feed\Application\UseCase\CreateFeed;
use Inavii\Instagram\Feed\Application\UseCase\DeleteFeed;
use Inavii\Instagram\Feed\Application\UseCase\GetMediaForView;
use Inavii\Instagram\Feed\Application\UseCase\UpdateFeedSettings;
use Inavii\Instagram\Feed\Domain\Exceptions\FeedNotFoundException;
use Inavii\Instagram\Feed\Domain\Feed;
use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Feed\Domain\MediaSlice;
use Inavii\Instagram\Feed\Storage\FeedRepository;

final class FeedService {
	private FeedRepository $feeds;
	private CreateFeed $createFeed;
	private UpdateFeedSettings $updateFeedSettings;
	private DeleteFeed $deleteFeed;
	private GetMediaForView $getMediaForView;

	public function __construct(
		FeedRepository $feeds,
		CreateFeed $createFeed,
		UpdateFeedSettings $updateFeedSettings,
		DeleteFeed $deleteFeed,
		GetMediaForView $getMediaForView
	) {
		$this->feeds              = $feeds;
		$this->createFeed         = $createFeed;
		$this->updateFeedSettings = $updateFeedSettings;
		$this->deleteFeed         = $deleteFeed;
		$this->getMediaForView    = $getMediaForView;
	}

	public function create( string $title, string $feedType, string $feedMode, FeedSettings $settings ): Feed {
		return $this->createFeed->handle( $title, $feedType, $feedMode, $settings );
	}

	public function updateSettings( int $feedId, string $title, FeedSettings $settings, string $feedMode ): void {
		$this->updateFeedSettings->handle( $feedId, $title, $settings, $feedMode );
	}

	public function delete( int $feedId ): void {
		$this->deleteFeed->handle( $feedId );
	}

	/**
	 * @param int $feedId
	 *
	 * @return void
	 */
	public function clearCache( int $feedId ): void {
		$this->feeds->get( $feedId );

		do_action( 'inavii/social-feed/front-index/delete', [ 'feedId' => $feedId ] );
	}

	/**
	 * @return array
	 */
	public function all(): array {
		return array_map(
			fn( Feed $feed ): array => $this->mapFeedForAdminApp( $feed ),
			$this->feeds->all()
		);
	}

	/**
	 * @param int $feedId
	 *
	 * @return Feed
	 *
	 * @throws FeedNotFoundException When feed does not exist.
	 */
	public function get( int $feedId ): Feed {
		return $this->feeds->get( $feedId );
	}

	public function getMedia( int $feedId, int $limit = 30, int $offset = 0 ): MediaSlice {
		return $this->getMediaForView->handle( $feedId, $limit, $offset );
	}

	public function getForAdminApp( int $feedId ): array {
		return $this->mapFeedForAdminApp( $this->feeds->get( $feedId ) );
	}

	public function getForFrontApp( int $feedId, int $limit = 30, int $offset = 0 ): array {
		$feed  = $this->feeds->get( $feedId );
		$media = $this->getMediaForView->handle( $feedId, $limit, $offset );

		return [
			'feed'  => [
				'id'       => $feed->id(),
				'title'    => $feed->title(),
				'feedType' => $feed->feedType(),
				'feedMode' => $feed->feedMode(),
				'settings' => $feed->settings()->toArray(),
				'sources'  => $feed->settings()->sources()->toArray(),
			],
			'media' => $media->posts(),
			'total' => $media->total(),
		];
	}

	private function mapFeedForAdminApp( Feed $feed ): array {
		return [
			'id'       => $feed->id(),
			'title'    => $feed->title(),
			'feedType' => $feed->feedType(),
			'feedMode' => $feed->feedMode(),
			'settings' => $feed->settings()->toArray(),
		];
	}

}
