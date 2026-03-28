<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\PostTypes\Feed;

use Inavii\Instagram\Includes\Legacy\Wp\Query;
use Inavii\Instagram\Wp\PostType;

final class FeedPostType extends PostType {

	public const META_KEY_FEEDS          = 'inavii_social_feed_feeds';
	public const META_KEY_FEEDS_SETTINGS = 'inavii_social_feed_settings';
	public const META_KEY_FEEDS_TYPE     = 'inavii_social_feed_type';
	public const INSTAGRAM_POSTS         = 'instagram_posts';

	public function slug(): string {
		return 'inavii_feed';
	}

	public function getSettings( int $postID ): array {
		$settings = $this->getMeta( $postID, self::META_KEY_FEEDS_SETTINGS );

		return is_array( $settings ) ? $settings : [];
	}

	public function getAccounts(): array {
		$posts = ( new Query( $this->slug() ) )->numberOfPosts()->order( 'DESC' )->posts()->getPosts();

		if ( empty( $posts ) ) {
			return [];
		}

		$formattedPosts = [];
		foreach ( $posts as $post ) {
			$postId = isset( $post->ID ) ? (int) $post->ID : 0;
			if ( $postId <= 0 ) {
				continue;
			}

			$title  = isset( $post->post_title ) ? (string) $post->post_title : '';
			$layout = $this->resolveLayout( $this->getSettings( $postId ) );
			$key    = $postId . ':' . $layout;
			$value  = $title !== '' ? $title : ( 'Feed #' . $postId );

			$formattedPosts[ $key ] = $value . ' (' . $layout . ')';
		}

		return $formattedPosts;
	}

	protected function args(): array {
		return array_merge(
			parent::args(),
			[
				'labels' => [
					'menu_name' => __( 'Inavii Feed', 'inavii-social-feed' ),
				],
			]
		);
	}

	private function resolveLayout( array $settings ): string {
		$layout = $settings['layout'] ?? null;
		if ( is_string( $layout ) && $layout !== '' ) {
			return $layout;
		}

		return 'grid';
	}
}
