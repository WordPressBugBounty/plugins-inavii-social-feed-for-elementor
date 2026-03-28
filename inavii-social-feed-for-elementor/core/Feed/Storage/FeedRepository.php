<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Storage;

use Inavii\Instagram\Feed\Domain\Exceptions\FeedNotFoundException;
use Inavii\Instagram\Feed\Domain\Feed;
use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;

final class FeedRepository {

	public const CPT                    = 'inavii_feed';
	public const META_KEY_FEED_TYPE     = 'inavii_social_feed_type';
	public const META_KEY_FEED_MODE     = 'inavii_social_feed_mode';
	public const META_KEY_FEED_SETTINGS = 'inavii_social_feed_settings';
	public const DEFAULT_FEED_TYPE      = 'instagram_posts';
	public const DEFAULT_FEED_MODE      = 'in_page_embed';

	private ProFeaturesPolicy $proFeatures;

	public function __construct( ?ProFeaturesPolicy $proFeatures = null ) {
		$this->proFeatures = $proFeatures ?? new ProFeaturesPolicy();
	}

	public function create( string $title ): int {
		$existing = new \WP_Query(
			[
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'title'          => $title,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			]
		);

		if ( ! empty( $existing->posts ) ) {
			$existingId = (int) $existing->posts[0];
			$updatedId  = wp_update_post(
				[
					'ID'         => $existingId,
					'post_title' => $title,
				],
				true
			);

			return is_wp_error( $updatedId ) ? 0 : (int) $updatedId;
		}

		$createdId = wp_insert_post(
			[
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => $title,
			],
			true
		);

		return is_wp_error( $createdId ) ? 0 : (int) $createdId;
	}

	public function save( Feed $feed ): void {
		wp_update_post(
			[
				'ID'         => $feed->id(),
				'post_title' => $feed->title(),
			]
		);

		update_post_meta( $feed->id(), self::META_KEY_FEED_TYPE, $feed->feedType() );
		update_post_meta( $feed->id(), self::META_KEY_FEED_MODE, $feed->feedMode() );
		update_post_meta( $feed->id(), self::META_KEY_FEED_SETTINGS, $feed->settings()->toArray() );
	}

	/**
	 * @throws FeedNotFoundException
	 */
	public function get( int $id ): Feed {
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::CPT ) {
			throw new FeedNotFoundException( $id );
		}

		return $this->hydrate( $post );
	}

	/**
	 * @return Feed[]
	 */
	public function all(): array {
		$q = new \WP_Query(
			[
				'post_type'              => self::CPT,
				'posts_per_page'         => -1,
				'post_status'            => 'any',
				'orderby'                => 'ID',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'fields'                 => 'all',
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			]
		);

		if ( empty( $q->posts ) ) {
			return [];
		}

		/** @var \WP_Post[] $posts */
		$posts = $q->posts;

		return array_map( fn( \WP_Post $p ) => $this->hydrate( $p ), $posts );
	}

	public function delete( int $id ): void {
		if ( $id <= 0 ) {
			return;
		}

		wp_delete_post( $id, true );
	}

	private function hydrate( \WP_Post $post ): Feed {
		$id = (int) $post->ID;

		return new Feed(
			$id,
			(string) $post->post_title,
			$this->readFeedType( $id ),
			$this->readFeedMode( $id ),
			$this->readSettings( $id )
		);
	}

	private function readFeedType( int $id ): string {
		$type = get_post_meta( $id, self::META_KEY_FEED_TYPE, true );
		if ( $type === '' || $type === null ) {
			return self::DEFAULT_FEED_TYPE;
		}

		return (string) $type;
	}

	private function readFeedMode( int $id ): string {
		$mode = get_post_meta( $id, self::META_KEY_FEED_MODE, true );
		if ( $mode === '' || $mode === null ) {
			return self::DEFAULT_FEED_MODE;
		}

		return $this->proFeatures->sanitizeFeedMode( (string) $mode );
	}

	private function readSettings( int $id ): FeedSettings {
		$raw = get_post_meta( $id, self::META_KEY_FEED_SETTINGS, true );
		return FeedSettings::fromArray( is_array( $raw ) ? $raw : [] );
	}
}
