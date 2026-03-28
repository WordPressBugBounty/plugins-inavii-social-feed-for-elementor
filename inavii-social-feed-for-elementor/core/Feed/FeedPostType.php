<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed;

use Inavii\Instagram\Wp\PostType;

final class FeedPostType extends PostType {

	public function slug(): string {
		return 'inavii_feed';
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
}
