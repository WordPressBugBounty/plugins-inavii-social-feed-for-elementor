<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front;

final class Config {
	/**
	 * Config consumed by React apps (front + admin preview).
	 */
	public function all(): array {
		$config = [
			'uiBehavior' => [
				'sidebar'        => [
					'breakpoint'    => 1023,
					'loadMoreStep'  => 30,
					'videoAutoplay' => true,
				],
				'mobileFriendly' => [
					'breakpoint'    => 1023,
					'loadMoreStep'  => 15,
					'videoAutoplay' => true,
				],
				'feed'           => [
					'loadMoreStep' => 20,
				],
				'highlight'      => [
					'loadMoreStep' => 9,
				],
				'slider'         => [
					'transition' => [
						'slider'      => 500,
						'showcase'    => 400,
						'sliderCards' => 400,
						'infinite'    => 3500,
					],
				],
			],
			'language'   => [

				/*
				 * Label keys: follow, viewOnInstagram, loadMore, posts, followers, following, more.
				 * Example:
				 * 'force' => false,
				 * 'global' => [ 'loadMore' => 'Show more' ],
				 * 'byLanguage' => [ 'pl' => [ 'followers' => 'Obserwujący' ] ],
				 */
				'force'      => false,
				'global'     => [],
				'byLanguage' => [],
			],
		];

		$filtered = apply_filters( 'inavii/social-feed/config', $config );

		return is_array( $filtered ) ? $filtered : $config;
	}
}
