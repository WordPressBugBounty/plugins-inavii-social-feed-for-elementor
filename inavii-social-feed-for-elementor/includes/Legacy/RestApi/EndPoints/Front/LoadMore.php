<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Front;

use Inavii\Instagram\Feed\Application\FeedService;
use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\Includes\Legacy\Front\LegacyFeedCacheReader;
use Inavii\Instagram\Includes\Legacy\Front\LegacyMediaViewMapper;
use Inavii\Instagram\Includes\Legacy\Front\LegacyPromotionHydrator;
use Inavii\Instagram\Includes\Legacy\Integration\Views\Views;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;
use function Inavii\Instagram\Di\container;

class LoadMore {

	private $api;
	private FeedService $feedService;
	private LegacyFeedCacheReader $legacyCache;
	private LegacyMediaViewMapper $legacyMapper;
	private LegacyPromotionHydrator $legacyPromotionHydrator;
	private $feedId;

	public function __construct() {
		$this->api         = new ApiResponse();
		$this->feedService = container()->get( FeedService::class );
		$this->legacyCache = new LegacyFeedCacheReader();
		$this->legacyMapper = new LegacyMediaViewMapper();
		$this->legacyPromotionHydrator = new LegacyPromotionHydrator();
	}

	public function get( WP_REST_Request $request ): WP_REST_Response {
		$widgetData = $request->get_param( 'settings' );

		if ( empty( $widgetData ) ) {
			return $this->apiResponse( false, 'No widget data' );
		}

		$feedId       = $this->sanitizeInt( $widgetData['feed_id'] ?? '' );
		$postCount    = $this->sanitizeInt( $widgetData['posts_count'] ?? '' );
		$feedOffset   = $this->sanitizeInt( $widgetData['feed_offset'] ?? 0 );
		$this->feedId = $feedId;

		$payload = $this->resolvePosts( $feedId, $postCount, $feedOffset );
		if ( $payload['items'] === [] ) {
			return $this->noPostsResponse();
		}

		return $this->postsResponse( $widgetData, $payload['items'], $payload['total'], $payload['feedSettings'] );
	}

	private function noPostsResponse(): WP_REST_Response {
		$html = Views::renderAjaxMessage( '<span>No posts</span> to display' );
		return $this->apiResponse(
			true,
			[
				'html'      => $html,
				'popupHtml' => '',
				'total'     => 0,
			]
		);
	}

	private function postsResponse( array $widgetData, array $items, int $total, array $feedSettings ): WP_REST_Response {
		$widgetData['is_promotion'] = $this->legacyPromotionHydrator->isPromotionEnabled( $feedSettings );
		$html = Views::renderFeedItems( array_merge( $widgetData, [ 'items' => $items ] ) );

		$photoLinking = isset( $widgetData['enable_photo_linking'] ) ? (string) $widgetData['enable_photo_linking'] : '';
		if ( $photoLinking === 'popup' ) {
			$popupHtml = Views::renderPopup( array_merge( $widgetData, [ 'items' => $items ] ) );
		} else {
			$popupHtml = Views::renderLightbox( array_merge( $widgetData, [ 'items' => $items ] ) );
		}

		return $this->apiResponse(
			true,
			[
				'html'      => $html,
				'popupHtml' => $popupHtml,
				'total'     => $total,
			]
		);
	}

	private function resolvePosts( int $feedId, int $postCount, int $feedOffset ): array {
		$feedSettings = $this->getFeedSettings( $feedId );

		try {
			$slice = $this->feedService->getMedia( $feedId, $postCount, $feedOffset );
			$items = $this->legacyMapper->mapPosts(
				$this->legacyPromotionHydrator->apply( $slice->getPosts(), $feedSettings )
			);
			if ( $items !== [] ) {
				return [
					'items'        => $items,
					'total'        => $slice->getTotal(),
					'feedSettings' => $feedSettings,
				];
			}
		} catch ( \Throwable $e ) {
			// Use legacy CPT fallback while data migration is still pending.
		}

		$fallback = $this->legacyCache->getSlice( $feedId, $postCount, $feedOffset );
		return [
			'items' => $this->legacyMapper->mapPosts(
				$this->legacyPromotionHydrator->apply( $fallback['items'], $feedSettings )
			),
			'total'        => (int) $fallback['total'],
			'feedSettings' => $feedSettings,
		];
	}

	private function getFeedSettings( int $feedId ): array {
		try {
			return $this->feedService->get( $feedId )->settings()->toArray();
		} catch ( \Throwable $e ) {
			$raw = get_post_meta( $feedId, FeedRepository::META_KEY_FEED_SETTINGS, true );

			return is_array( $raw ) ? $raw : [];
		}
	}

	private function sanitizeInt( $value ): int {
		return (int) filter_var( $value, FILTER_SANITIZE_NUMBER_INT );
	}

	private function apiResponse( bool $success, $data = [] ): WP_REST_Response {
		return $this->api->response(
			[
				'success' => $success,
				'data'    => $data,
			]
		);
	}
}
