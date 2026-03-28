<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Media;

use Inavii\Instagram\Feed\Application\UseCase\PreviewFeedSources;
use Inavii\Instagram\Feed\Domain\FeedMediaFilters;
use Inavii\Instagram\Feed\Domain\FeedSources;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

final class MediaSourceController {
	private PreviewFeedSources $preview;
	private ApiResponse $api;

	public function __construct( PreviewFeedSources $preview, ApiResponse $api ) {
		$this->preview = $preview;
		$this->api     = $api;
	}

	public function create( WP_REST_Request $request ): WP_REST_Response {
		$source = $request->get_param( 'source' );
		$source = is_array( $source ) ? $source : [];

		try {
			$sources = FeedSources::fromArray( $source );
			$limit   = (int) apply_filters( 'inavii/social-feed/media/fetch_limit', 50 );
			$limit   = max( 1, $limit );

			$result = $this->preview->handle(
				$sources,
				$limit,
				0,
				true,
				FeedMediaFilters::fromArray( [] ),
				0
			);

			$items  = isset( $result['items'] ) && is_array( $result['items'] ) ? $result['items'] : [];
			$media  = $this->mapLegacyMedia( $items );
			$unique = [];
			foreach ( $media as $item ) {
				$key = isset( $item['id'] ) ? (string) $item['id'] : '';
				if ( $key === '' ) {
					$key = isset( $item['mediaId'] ) ? (string) $item['mediaId'] : '';
				}

				if ( $key !== '' && ! isset( $unique[ $key ] ) ) {
					$unique[ $key ] = $item;
				}
			}

			return $this->api->response(
				[
					'media' => array_values( $unique ),
				]
			);
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		}
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

			$id = isset( $post['id'] ) ? (string) $post['id'] : '';
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
}
