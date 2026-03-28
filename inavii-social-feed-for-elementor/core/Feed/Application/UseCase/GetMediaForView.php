<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Application\UseCase;

use Inavii\Instagram\Feed\Domain\Exceptions\FeedNotFoundException;
use Inavii\Instagram\Feed\Domain\MediaSlice;
use Inavii\Instagram\Feed\Domain\Policy\SourceMixModePolicy;
use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\Media\Application\MediaAccountProfileHydrator;
use Inavii\Instagram\Media\Application\MediaPostsFinder;
use Inavii\Instagram\Media\Source\Storage\FeedSourcesRepository;

final class GetMediaForView {

	private FeedRepository $feeds;
	private FeedSourcesRepository $sources;
	private MediaPostsFinder $finder;
	private MediaAccountProfileHydrator $profiles;
	private SourceMixModePolicy $sourceMixMode;

	public function __construct(
		FeedRepository $feeds,
		FeedSourcesRepository $sources,
		MediaPostsFinder $finder,
		MediaAccountProfileHydrator $profiles,
		SourceMixModePolicy $sourceMixMode
	) {
		$this->feeds         = $feeds;
		$this->sources       = $sources;
		$this->finder        = $finder;
		$this->profiles      = $profiles;
		$this->sourceMixMode = $sourceMixMode;
	}

	public function handle( int $feedId, int $limit = 30, int $offset = 0 ): MediaSlice {
		try {
			$feed = $this->feeds->get( $feedId );
		} catch ( FeedNotFoundException $e ) {
			return MediaSlice::empty();
		}

		$sourceKeys = $this->sources->getSourceKeysByFeedId( $feedId );
		if ( $sourceKeys === [] ) {
			return MediaSlice::empty();
		}

		$filters                  = $feed->settings()->mediaFilters()->toQueryArgs();
		$filters['sourceMixMode'] = $this->sourceMixMode->resolve( $feedId, $sourceKeys, $filters );
		$posts                    = $this->finder->bySourceKeysFiltered( $sourceKeys, $filters, $limit, $offset );
		$posts                    = $this->profiles->hydrate( $posts );
		$total                    = $this->finder->countBySourceKeysFiltered( $sourceKeys, $filters );

		return new MediaSlice( $posts, $total );
	}
}
