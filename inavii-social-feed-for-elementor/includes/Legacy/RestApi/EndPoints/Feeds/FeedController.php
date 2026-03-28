<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Feeds;

use Inavii\Instagram\Feed\Application\FeedService;
use Inavii\Instagram\Feed\Domain\Exceptions\FeedNotFoundException;
use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Includes\Legacy\Front\LegacyFeedCacheReader;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyFeedLayoutMap;
use Inavii\Instagram\Includes\Legacy\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Includes\Legacy\PostTypes\Media\MediaPostType;
use Inavii\Instagram\Includes\Legacy\RestApi\Mapper\LegacySettingsToV3Mapper;
use Inavii\Instagram\Includes\Legacy\Wp\Query;
use Inavii\Instagram\Media\Application\MediaPostsFinder;
use Inavii\Instagram\Media\Source\Storage\FeedSourcesRepository;
use Inavii\Instagram\Media\Source\Storage\SourcesRepository;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class FeedController {

	private FeedService $feedService;
	private ApiResponse $api;
	private LegacyFeedCacheReader $legacyCache;
	private FeedPostType $legacyFeedPostType;
	private MediaPostType $legacyMediaPostType;
	private LegacySettingsToV3Mapper $legacySettingsMapper;
	private FeedSourcesRepository $feedSources;
	private SourcesRepository $sources;
	private MediaPostsFinder $mediaFinder;

	public function __construct(
		FeedService $feedService,
		ApiResponse $api,
		LegacySettingsToV3Mapper $legacySettingsMapper,
		FeedSourcesRepository $feedSources,
		SourcesRepository $sources,
		MediaPostsFinder $mediaFinder
	) {
		$this->api                  = $api;
		$this->feedService          = $feedService;
		$this->legacyCache          = new LegacyFeedCacheReader();
		$this->legacyFeedPostType   = new FeedPostType();
		$this->legacyMediaPostType  = new MediaPostType();
		$this->legacySettingsMapper = $legacySettingsMapper;
		$this->feedSources          = $feedSources;
		$this->sources              = $sources;
		$this->mediaFinder          = $mediaFinder;
	}

	public function get( WP_REST_Request $request ): WP_REST_Response {
		$feedID = (int) $request->get_param( 'id' );
		if ( $feedID <= 0 ) {
			return $this->api->response( [], false, 'Invalid feed id.' );
		}

		$numberOfPosts = absint( (string) $request->get_param( 'numberOfPosts' ) );
		if ( $numberOfPosts <= 0 ) {
			$numberOfPosts = 30;
		}

		try {
			$feed     = $this->feedService->get( $feedID );
			$settings = $this->toLegacySettings( $feed->settings()->toArray() );

			$mediaRows = $this->loadPreviewMediaRows( $feedID, $numberOfPosts );
			$media     = $this->mapLegacyMedia( $mediaRows );

			if ( $media === [] ) {
				$slice = $this->feedService->getMedia( $feedID, $numberOfPosts, 0 );
				$media = $this->mapLegacyMedia( $slice->getPosts() );
			}

			if ( $media === [] ) {
				$fallback = $this->legacyCache->getSlice( $feedID, $numberOfPosts, 0 );
				$media    = $this->mapLegacyMedia( $fallback['items'] );
			}

			$settings = $this->normalizeLegacyModerationForEditor( $feedID, $settings, $media );
			$settings = $this->normalizeLegacyPromotionsForEditor( $settings, $media );

			return $this->api->response(
				[
					'media'            => $media,
					'settings'         => $settings,
					'feedType'         => $feed->feedType(),
					'migrateAccountId' => $this->resolveMigrateAccountId( $feedID, $settings ),
				]
			);
		} catch ( FeedNotFoundException $e ) {
			if ( ! $this->isLegacyFeed( $feedID ) ) {
				return $this->api->response( [], false, 'Feed not found.' );
			}

			return $this->api->response( $this->legacyFeedForApi( $feedID, $numberOfPosts ) );
		} catch ( \Throwable $e ) {
			if ( ! $this->isLegacyFeed( $feedID ) ) {
				return $this->api->response( [], false, 'Feed not found.' );
			}

			return $this->api->response( $this->legacyFeedForApi( $feedID, $numberOfPosts ) );
		}
	}

	public function all(): WP_REST_Response {
		try {
			$feeds = $this->feedService->all();
		} catch ( \Throwable $e ) {
			$feeds = [];
		}

		if ( $feeds === [] ) {
			return $this->api->response( $this->legacyFeedsForApi() );
		}

		$feeds = array_map(
			function ( array $feed ): array {
				$id       = isset( $feed['id'] ) ? (int) $feed['id'] : 0;
				$settings = isset( $feed['settings'] ) && is_array( $feed['settings'] )
					? $this->toLegacySettings( $feed['settings'] )
					: $this->toLegacySettings( [] );

				return [
					'id'              => $id,
					'title'           => isset( $feed['title'] ) ? (string) $feed['title'] : '',
					'feedType'        => isset( $feed['feedType'] ) ? (string) $feed['feedType'] : '',
					'settings'        => $settings,
					'accountID'       => $this->resolvePrimaryAccountId( $settings ),
					'lastUpdatedPost' => $this->resolveFeedLastUpdated( $id, $settings ),
				];
			},
			$feeds
		);

		return $this->api->response(
			$feeds
		);
	}

	public function create( WP_REST_Request $request ): WP_REST_Response {
		try {
			$data = $request->get_params();

			$title    = sanitize_text_field( $data['title'] ?? '' );
			$settings = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : [];
			$feedType = $data['feedType'] ?? 'instagram_posts';
			$feedMode = $data['feedMode'] ?? '';

			$settings = $this->legacySettingsMapper->map( $settings );
			$settings = FeedSettings::fromArray( $settings );

			$feed = $this->feedService->create( $title, $feedType, (string) $feedMode, $settings );

			return $this->api->response(
				[
					'feedId' => $feed->id(),
				]
			);
		} catch ( \InvalidArgumentException $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		$data = $request->get_params();

		$postId   = isset( $data['postId'] ) ? absint( $data['postId'] ) : 0;
		$settings = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : [];
		$title    = sanitize_text_field( (string) ( $data['title'] ?? '' ) );

		if ( empty( $postId ) ) {
			return $this->api->response( [], false, 'Post ID is required' );
		}

		try {
			$feedMode = isset( $data['feedMode'] ) ? (string) $data['feedMode'] : '';
			$settings = $this->legacySettingsMapper->map( $settings );
			$this->feedService->updateSettings( (int) $postId, $title, FeedSettings::fromArray( $settings ), $feedMode );

			return $this->api->response( [ 'message' => 'Feed updated' ] );
		} catch ( FeedNotFoundException $e ) {
			return $this->api->response( [], false, 'Feed not found.' );
		} catch ( \InvalidArgumentException $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	public function delete( WP_REST_Request $request ): WP_REST_Response {
		$feedID = absint( (string) $request->get_param( 'id' ) );
		if ( $feedID <= 0 ) {
			return $this->api->response( [], false, 'Invalid feed id.' );
		}

		try {
			$this->feedService->delete( $feedID );

			return $this->api->response( [] );
		} catch ( FeedNotFoundException $e ) {
			return $this->api->response( [], false, 'Feed not found.' );
		} catch ( \InvalidArgumentException $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	private function toLegacySettings( array $settings ): array {
		$legacy = $settings;
		$source = isset( $legacy['source'] ) && is_array( $legacy['source'] ) ? $legacy['source'] : [];
		$filters = isset( $legacy['filters'] ) && is_array( $legacy['filters'] ) ? $legacy['filters'] : [];

		$legacy['source'] = [
			'accounts' => $this->sanitizeIntList( $source['accounts'] ?? [] ),
			'tagged'   => $this->sanitizeIntList( $source['tagged'] ?? [] ),
			'hashtags' => $this->normalizeLegacyHashtags( $legacy, $source ),
		];

		if ( ! isset( $legacy['layout'] ) || ! is_string( $legacy['layout'] ) || trim( $legacy['layout'] ) === '' ) {
			$legacy['layout'] = $this->resolveLegacyLayout( $legacy );
		}

		if ( ! isset( $legacy['postOrder'] ) || ! is_string( $legacy['postOrder'] ) || trim( $legacy['postOrder'] ) === '' ) {
			$legacy['postOrder'] = $this->resolveLegacyPostOrder( $legacy );
		}

		if ( ! isset( $legacy['typesOfPosts'] ) || ! is_array( $legacy['typesOfPosts'] ) ) {
			$types                  = $filters['typesOfPosts'] ?? [];
			$legacy['typesOfPosts'] = is_array( $types ) ? array_values( $types ) : [ 'IMAGE', 'VIDEO', 'CAROUSEL_ALBUM' ];
		}

		if ( ! isset( $legacy['moderateHidePost'] ) ) {
			$legacy['moderateHidePost'] = $this->toBool( $filters['moderationEnabled'] ?? false );
		}

		if ( ! isset( $legacy['moderationMode'] ) || ! is_string( $legacy['moderationMode'] ) || trim( $legacy['moderationMode'] ) === '' ) {
			$legacy['moderationMode'] = $this->resolveLegacyModerationMode( $filters );
		}

		if ( ! isset( $legacy['moderation'] ) || ! is_array( $legacy['moderation'] ) ) {
			$legacy['moderation'] = $this->resolveLegacyModeration( $filters );
		}

		if ( ! isset( $legacy['captionFilter'] ) || ! is_array( $legacy['captionFilter'] ) ) {
			$legacy['captionFilter'] = $this->resolveLegacyTextFilter( $filters['captionFilter'] ?? [] );
		}

		if ( ! isset( $legacy['hashTagFilter'] ) || ! is_array( $legacy['hashTagFilter'] ) ) {
			$legacy['hashTagFilter'] = $this->resolveLegacyTextFilter( $filters['hashtagFilter'] ?? [] );
		}

		if ( ! isset( $legacy['promotion'] ) ) {
			$legacyCustomLinks    = isset( $filters['customLinks'] ) && is_array( $filters['customLinks'] ) ? $filters['customLinks'] : [];
			$legacyCustomLinksMap = isset( $legacyCustomLinks['byPostId'] ) && is_array( $legacyCustomLinks['byPostId'] ) ? $legacyCustomLinks['byPostId'] : [];

			$legacy['promotion'] = $this->toBool( $filters['customLinksEnabled'] ?? false ) || $legacyCustomLinksMap !== [];
		}

		if ( ! isset( $legacy['promotionsData'] ) || ! is_array( $legacy['promotionsData'] ) ) {
			$legacy['promotionsData'] = $this->resolveLegacyPromotionsData( $filters );
		}

		return $legacy;
	}

	/**
	 * @param array $filters
	 */
	private function resolveLegacyModerationMode( array $filters ): string {
		$mode = '';
		if ( isset( $filters['moderationSelection'] ) && is_array( $filters['moderationSelection'] ) ) {
			$mode = isset( $filters['moderationSelection']['selectedState'] ) ? (string) $filters['moderationSelection']['selectedState'] : '';
		}

		if ( $mode === '' ) {
			$mode = isset( $filters['moderationMode'] ) ? (string) $filters['moderationMode'] : '';
		}

		$mode = strtolower( trim( $mode ) );

		return $mode === 'show' ? 'whitelist' : 'blacklist';
	}

	/**
	 * @param array $filters
	 *
	 * @return string[]
	 */
	private function resolveLegacyModeration( array $filters ): array {
		$postIds = [];
		if ( isset( $filters['moderationSelection'] ) && is_array( $filters['moderationSelection'] ) ) {
			$postIds = $filters['moderationSelection']['postIds'] ?? [];
		}

		if ( ! is_array( $postIds ) ) {
			$postIds = [];
		}

		$out = [];
		foreach ( $postIds as $postId ) {
			if ( ! is_scalar( $postId ) ) {
				continue;
			}

			$normalized = trim( (string) $postId );
			if ( $normalized !== '' ) {
				$out[] = $normalized;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param mixed $rawFilter
	 *
	 * @return array{include: array, exclude: array}
	 */
	private function resolveLegacyTextFilter( $rawFilter ): array {
		if ( ! is_array( $rawFilter ) ) {
			return [
				'include' => [],
				'exclude' => [],
			];
		}

		$include = isset( $rawFilter['include'] ) && is_array( $rawFilter['include'] ) ? array_values( $rawFilter['include'] ) : [];
		$exclude = isset( $rawFilter['exclude'] ) && is_array( $rawFilter['exclude'] ) ? array_values( $rawFilter['exclude'] ) : [];

		return [
			'include' => $include,
			'exclude' => $exclude,
		];
	}

	/**
	 * @param array $filters
	 *
	 * @return array<string,array<string,string>>
	 */
	private function resolveLegacyPromotionsData( array $filters ): array {
		$customLinks = isset( $filters['customLinks'] ) && is_array( $filters['customLinks'] ) ? $filters['customLinks'] : [];
		$byPostId    = isset( $customLinks['byPostId'] ) && is_array( $customLinks['byPostId'] ) ? $customLinks['byPostId'] : [];

		$out = [];
		foreach ( $byPostId as $postId => $config ) {
			if ( ! is_scalar( $postId ) || ! is_array( $config ) ) {
				continue;
			}

			$normalizedPostId = trim( (string) $postId );
			if ( $normalizedPostId === '' ) {
				continue;
			}

			$linkUrl = isset( $config['linkUrl'] ) && is_scalar( $config['linkUrl'] ) ? trim( (string) $config['linkUrl'] ) : '';
			$buttonText = isset( $config['buttonText'] ) && is_scalar( $config['buttonText'] ) ? trim( (string) $config['buttonText'] ) : '';
			$openMode = isset( $config['openMode'] ) && is_scalar( $config['openMode'] ) ? strtolower( trim( (string) $config['openMode'] ) ) : 'new';

			$out[ $normalizedPostId ] = [
				'linkSource'       => $linkUrl !== '' ? 'custom' : 'instagram',
				'linkUrl'          => $linkUrl,
				'target'           => $openMode === 'same' ? '_self' : '_blank',
				'buttonModalTitle' => $buttonText,
			];
		}

		return $out;
	}

	/**
	 * @param mixed $value
	 */
	private function toBool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value !== 0;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			return in_array( $value, [ '1', 'true', 'yes', 'on' ], true );
		}

		return false;
	}

	/**
	 * @param array $settings
	 * @param array $source
	 *
	 * @return array
	 */
	private function normalizeLegacyHashtags( array $settings, array $source ): array {
		$out    = [];
		$config = $settings['hashtagConfig'] ?? [];

		if ( is_array( $config ) ) {
			foreach ( $config as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$id = isset( $item['id'] ) ? trim( (string) $item['id'] ) : '';
				if ( $id === '' ) {
					continue;
				}

				$type = isset( $item['type'] ) ? trim( (string) $item['type'] ) : 'top_media';
				$type = $type !== '' ? $type : 'top_media';

				$out[ $id ] = [
					'id'   => $id,
					'type' => $type,
				];
			}
		}

		$hashtags = $source['hashtags'] ?? [];
		if ( is_array( $hashtags ) ) {
			foreach ( $hashtags as $item ) {
				if ( is_array( $item ) ) {
					$id   = isset( $item['id'] ) ? trim( (string) $item['id'] ) : '';
					$type = isset( $item['type'] ) ? trim( (string) $item['type'] ) : 'top_media';
				} else {
					$id   = trim( (string) $item );
					$type = 'top_media';
				}

				$id = ltrim( $id, '#' );
				if ( $id === '' ) {
					continue;
				}

				if ( ! isset( $out[ $id ] ) ) {
					$out[ $id ] = [
						'id'   => $id,
						'type' => $type !== '' ? $type : 'top_media',
					];
				}
			}
		}

		return array_values( $out );
	}

	/**
	 * @param mixed $items
	 *
	 * @return int[]
	 */
	private function sanitizeIntList( $items ): array {
		if ( ! is_array( $items ) ) {
			return [];
		}

		$ids = array_map( 'absint', $items );
		$ids = array_filter(
			$ids,
			static function ( int $id ): bool {
				return $id > 0;
			}
		);

		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param array $settings
	 */
	private function resolveLegacyLayout( array $settings ): string {
		$feedLayout = $settings['design']['feedLayout'] ?? [];
		if ( ! is_array( $feedLayout ) ) {
			return 'grid';
		}

		$layout = LegacyFeedLayoutMap::toLegacy( $feedLayout );

		return is_string( $layout ) && $layout !== '' ? $layout : 'grid';
	}

	/**
	 * @param array $settings
	 */
	private function resolveLegacyPostOrder( array $settings ): string {
		$orderBy = $settings['filters']['orderBy'] ?? 'recent';
		$orderBy = strtolower( trim( (string) $orderBy ) );

		if ( $orderBy === 'likes' ) {
			return 'likeCount';
		}
		if ( $orderBy === 'comments' ) {
			return 'commentCount';
		}
		if ( $orderBy === 'oldest' ) {
			return 'oldestFirst';
		}
		if ( $orderBy === 'random' ) {
			return 'random';
		}

		return 'mostRecentFirst';
	}

	/**
	 * @param array $settings
	 */
	private function resolvePrimaryAccountId( array $settings ): int {
		$source   = isset( $settings['source'] ) && is_array( $settings['source'] ) ? $settings['source'] : [];
		$accounts = $this->sanitizeIntList( $source['accounts'] ?? [] );
		if ( $accounts !== [] ) {
			return (int) $accounts[0];
		}

		$tagged = $this->sanitizeIntList( $source['tagged'] ?? [] );
		if ( $tagged !== [] ) {
			return (int) $tagged[0];
		}

		return 0;
	}

	/**
	 * @param array $posts
	 *
	 * @return array
	 */
	private function mapLegacyMedia( array $posts ): array {
		$mapped = [];
		foreach ( $posts as $post ) {
			if ( ! is_array( $post ) ) {
				continue;
			}

			$medium = isset( $post['mediaUrl']['thumbnail'] ) ? (string) $post['mediaUrl']['thumbnail'] : '';
			if ( $medium === '' && isset( $post['mediaUrl']['medium'] ) ) {
				$medium = (string) $post['mediaUrl']['medium'];
			}
			$large  = isset( $post['mediaUrl']['large'] ) ? (string) $post['mediaUrl']['large'] : '';
			$full   = $large !== '' ? $large : $medium;
			if ( $medium === '' ) {
				$medium = $full;
			}

			$children = [];
			if ( isset( $post['children'] ) && is_array( $post['children'] ) ) {
				foreach ( $post['children'] as $child ) {
					if ( ! is_array( $child ) ) {
						continue;
					}
					$childMedium = isset( $child['mediaUrl']['thumbnail'] ) ? (string) $child['mediaUrl']['thumbnail'] : '';
					if ( $childMedium === '' && isset( $child['mediaUrl']['medium'] ) ) {
						$childMedium = (string) $child['mediaUrl']['medium'];
					}
					$childLarge  = isset( $child['mediaUrl']['large'] ) ? (string) $child['mediaUrl']['large'] : '';
					$childFull   = $childLarge !== '' ? $childLarge : $childMedium;
					if ( $childMedium === '' ) {
						$childMedium = $childFull;
					}

					$children[] = [
						'id'        => isset( $child['id'] ) ? (string) $child['id'] : '',
						'url'       => $childFull,
						'videoUrl'  => isset( $child['videoUrl'] ) ? (string) $child['videoUrl'] : '',
						'mediaType' => isset( $child['mediaType'] ) ? (string) $child['mediaType'] : '',
						'mediaUrl'  => [
							'small'  => $childMedium,
							'medium' => $childMedium,
							'large'  => $childFull,
						],
					];
				}
			}

			$id      = isset( $post['id'] ) ? (string) $post['id'] : '';
			$mediaId = isset( $post['mediaId'] ) ? (string) $post['mediaId'] : '';
			if ( $mediaId === '' ) {
				$mediaId = $id;
			}

			$mapped[] = [
				'id'            => $id,
				'mediaId'       => $mediaId,
				'url'           => $full,
				'feedType'      => 'instagram_posts',
				'mediaType'     => isset( $post['mediaType'] ) ? (string) $post['mediaType'] : '',
				'likeCount'     => isset( $post['likeCount'] ) ? (int) $post['likeCount'] : 0,
				'commentsCount' => isset( $post['commentsCount'] ) ? (int) $post['commentsCount'] : 0,
				'isLocked'      => isset( $post['isLocked'] ) ? (bool) $post['isLocked'] : false,
				'show'          => array_key_exists( 'show', $post ) ? (bool) $post['show'] : true,
				'mediaUrl'      => [
					'full'   => $full,
					'small'  => $medium,
					'medium' => $medium,
					'large'  => $full,
				],
				'date'          => isset( $post['date'] ) ? (string) $post['date'] : (string) ( $post['postedAt'] ?? '' ),
				'videoUrl'      => isset( $post['videoUrl'] ) ? (string) $post['videoUrl'] : '',
				'caption'       => isset( $post['caption'] ) ? (string) $post['caption'] : '',
				'children'      => $children,
				'promotion'     => isset( $post['promotion'] ) && is_array( $post['promotion'] ) ? $post['promotion'] : null,
			];
		}

		return $mapped;
	}

	/**
	 * @param int   $feedId
	 * @param array $settings
	 */
	private function resolveMigrateAccountId( int $feedId, array $settings ): int {
		$related = absint( (string) get_post_meta( $feedId, 'inavii_social_feed_account_related', true ) );
		if ( $related > 0 ) {
			return $related;
		}

		return $this->resolvePrimaryAccountId( $settings );
	}

	private function isLegacyFeed( int $feedId ): bool {
		$post = get_post( $feedId );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return $post->post_type === $this->legacyFeedPostType->slug();
	}

	private function legacyFeedForApi( int $feedId, int $numberOfPosts ): array {
		$settings = $this->toLegacySettings( $this->legacyFeedPostType->getSettings( $feedId ) );
		$slice    = $this->legacyCache->getSlice( $feedId, $numberOfPosts, 0 );
		$feedType = (string) get_post_meta( $feedId, FeedPostType::META_KEY_FEEDS_TYPE, true );

		if ( trim( $feedType ) === '' ) {
			$feedType = FeedPostType::INSTAGRAM_POSTS;
		}

		return [
			'media'            => $this->mapLegacyMedia( $slice['items'] ),
			'settings'         => $settings,
			'feedType'         => $feedType,
			'migrateAccountId' => $this->resolveMigrateAccountId( $feedId, $settings ),
		];
	}

	/**
	 * @return array
	 */
	private function loadPreviewMediaRows( int $feedId, int $limit ): array {
		$sourceKeys = $this->feedSources->getSourceKeysByFeedId( $feedId );
		if ( $sourceKeys === [] ) {
			return [];
		}

		return $this->mediaFinder->bySourceKeys( $sourceKeys, $limit, 0 );
	}

	/**
	 * Legacy React marks hidden posts by internal media row id (`post.id`),
	 * while V3 stores moderation as Instagram media id.
	 *
	 * @param int   $feedId
	 * @param array $settings
	 *
	 * @return array
	 */
	private function normalizeLegacyModerationForEditor( int $feedId, array $settings, array $media = [] ): array {
		if ( $feedId <= 0 ) {
			return $settings;
		}

		$moderation = isset( $settings['moderation'] ) && is_array( $settings['moderation'] )
			? $settings['moderation']
			: [];

		$normalized = [];
		foreach ( $moderation as $id ) {
			if ( ! is_scalar( $id ) ) {
				continue;
			}

			$id = trim( (string) $id );
			if ( $id !== '' ) {
				$normalized[] = $id;
			}
		}

		if ( $normalized === [] ) {
			$settings['moderation'] = [];
			return $settings;
		}

		$byMediaId = $this->buildLegacyEditorIdMap( $media );
		if ( $this->hasUnresolvedLegacyEditorIds( $normalized, $byMediaId ) ) {
			$sourceKeys = $this->feedSources->getSourceKeysByFeedId( $feedId );
			if ( $sourceKeys !== [] ) {
				$rows = $this->mediaFinder->bySourceKeysFiltered(
					$sourceKeys,
					[
						'moderationEnabled' => true,
						'moderationMode'    => 'show',
						'moderationPostIds' => $normalized,
					],
					max( 1, count( $normalized ) ),
					0
				);

				$byMediaId = array_replace( $byMediaId, $this->buildLegacyEditorIdMap( $this->mapLegacyMedia( $rows ) ) );
			}
		}

		$mapped = [];
		foreach ( $normalized as $id ) {
			$mapped[] = isset( $byMediaId[ $id ] ) ? $byMediaId[ $id ] : $id;
		}

		$settings['moderation'] = array_values( array_unique( $mapped ) );

		return $settings;
	}

	/**
	 * Legacy React expects promotionsData to be keyed by internal preview item id
	 * (`media.id`), while V3 stores custom links under Instagram media id.
	 *
	 * @param array $settings
	 * @param array $media
	 *
	 * @return array
	 */
	private function normalizeLegacyPromotionsForEditor( array $settings, array $media ): array {
		$promotions = isset( $settings['promotionsData'] ) && is_array( $settings['promotionsData'] )
			? $settings['promotionsData']
			: [];

		if ( $promotions === [] ) {
			$settings['promotionsData'] = [];
			return $settings;
		}

		$byMediaId = $this->buildLegacyEditorIdMap( $media );
		if ( $byMediaId === [] ) {
			return $settings;
		}

		$mapped = [];
		foreach ( $promotions as $postId => $config ) {
			if ( ! is_scalar( $postId ) || ! is_array( $config ) ) {
				continue;
			}

			$normalizedPostId = trim( (string) $postId );
			if ( $normalizedPostId === '' ) {
				continue;
			}

			$mapped[ $byMediaId[ $normalizedPostId ] ?? $normalizedPostId ] = $config;
		}

		$settings['promotionsData'] = $mapped;

		return $settings;
	}

	/**
	 * @param array $media
	 *
	 * @return array<string,string>
	 */
	private function buildLegacyEditorIdMap( array $media ): array {
		$map = [];
		foreach ( $media as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$mediaId = isset( $item['mediaId'] ) ? trim( (string) $item['mediaId'] ) : '';
			$id      = isset( $item['id'] ) ? trim( (string) $item['id'] ) : '';
			if ( $mediaId !== '' && $id !== '' ) {
				$map[ $mediaId ] = $id;
			}
		}

		return $map;
	}

	/**
	 * @param string[]            $ids
	 * @param array<string,string> $byMediaId
	 */
	private function hasUnresolvedLegacyEditorIds( array $ids, array $byMediaId ): bool {
		foreach ( $ids as $id ) {
			if ( ! isset( $byMediaId[ $id ] ) ) {
				return true;
			}
		}

		return false;
	}

	private function legacyFeedsForApi(): array {
		$posts = ( new Query( $this->legacyFeedPostType->slug() ) )
			->numberOfPosts()
			->order( 'DESC' )
			->posts()
			->getPosts();

		if ( $posts === [] ) {
			return [];
		}

		$feeds = [];
		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$feedId   = (int) $post->ID;
			$settings = $this->toLegacySettings( $this->legacyFeedPostType->getSettings( $feedId ) );
			$slice    = $this->legacyCache->getSlice( $feedId, 1, 0 );
			$items    = $slice['items'];

			$feedType = (string) get_post_meta( $feedId, FeedPostType::META_KEY_FEEDS_TYPE, true );
			if ( trim( $feedType ) === '' ) {
				$feedType = FeedPostType::INSTAGRAM_POSTS;
			}

			$feeds[] = [
				'id'              => $feedId,
				'title'           => (string) $post->post_title,
				'feedType'        => $feedType,
				'settings'        => $settings,
				'accountID'       => $this->resolveMigrateAccountId( $feedId, $settings ),
				'lastUpdatedPost' => $this->resolveFeedLastUpdated( $feedId, $settings ),
			];
		}

		return $feeds;
	}

	private function resolveFeedLastUpdated( int $feedId, array $settings ): string {
		$sourceKeys = $this->feedSources->getSourceKeysByFeedId( $feedId );
		if ( $sourceKeys !== [] ) {
			$latest = $this->latestSourceTimestamp( $this->sources->getByKeys( $sourceKeys ) );
			if ( $latest !== '' ) {
				return $this->normalizeLegacyDateTime( $latest );
			}
		}

		$legacySource = isset( $settings['source'] ) && is_array( $settings['source'] ) ? $settings['source'] : [];
		if ( $legacySource !== [] ) {
			$lastRequested = (string) ( $this->legacyMediaPostType->getMostRecentPostDate( $legacySource ) ?? '' );
			if ( trim( $lastRequested ) !== '' ) {
				return $this->normalizeLegacyDateTime( $lastRequested );
			}
		}

		return '';
	}

	private function latestSourceTimestamp( array $rows ): string {
		$latestValue     = '';
		$latestTimestamp = null;

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			foreach ( [ 'last_success_at', 'last_sync_at' ] as $field ) {
				$value = isset( $row[ $field ] ) ? trim( (string) $row[ $field ] ) : '';
				if ( $value === '' ) {
					continue;
				}

				$timestamp = strtotime( $value );
				if ( $timestamp === false ) {
					continue;
				}

				if ( $latestTimestamp === null || $timestamp > $latestTimestamp ) {
					$latestTimestamp = $timestamp;
					$latestValue     = $value;
				}
			}
		}

		return $latestValue;
	}

	private function normalizeLegacyDateTime( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		try {
			$date = new \DateTimeImmutable( $value, wp_timezone() );
			return $date->format( DATE_ATOM );
		} catch ( \Throwable $e ) {
			return $value;
		}
	}
}
