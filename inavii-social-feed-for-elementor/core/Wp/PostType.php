<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Wp;

abstract class PostType {


	abstract public function slug(): string;

	protected function args(): array {
		return [
			'supports'            => [ 'title', 'custom-fields' ],
			'show_ui'             => false,
			'show_in_rest'        => true,
			'query_var'           => true,
			'hierarchical'        => false,
			'public'              => false,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
		];
	}

	public function delete( $postID ) {
		return wp_delete_post( $postID, true );
	}

	protected function getMeta( int $postID, string $metaKey ) {
		return get_post_meta( $postID, $metaKey, true );
	}

	protected function updateMeta( int $postID, string $metaKey, $metaValue ): void {
		update_post_meta( $postID, $metaKey, $metaValue );
	}

	public static function register( PostType $postType ): void {
		if ( ! post_type_exists( $postType->slug() ) ) {
			register_post_type( $postType->slug(), $postType->args() );
		}
	}

	public function deleteAllPosts() {
		$mediaPostType = get_posts(
			[
				'post_type'   => $this->slug(),
				'numberposts' => -1,
				'post_status' => 'any',
			]
		);

		foreach ( $mediaPostType as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}
}
