<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

use Inavii\Instagram\FrontIndex\Domain\Policy\HashtagLabelPolicy;

final class HashtagMetaPresenter {
	private HashtagLabelPolicy $labelPolicy;

	public function __construct( HashtagLabelPolicy $labelPolicy ) {
		$this->labelPolicy = $labelPolicy;
	}

	/**
	 * @param string[] $hashtags
	 * @param string   $followLabel
	 */
	public function buildHeaderFallback( array $hashtags, string $followLabel ): ?array {
		if ( $hashtags === [] ) {
			return null;
		}

		$primaryTag   = $hashtags[0];
		$hashtagLabel = $this->labelPolicy->buildLabel( $hashtags );

		return [
			'name'        => $hashtagLabel,
			'username'    => $hashtagLabel,
			'avatarUrl'   => '',
			'posts'       => 0,
			'followers'   => 0,
			'following'   => 0,
			'profileUrl'  => $this->buildProfileUrl( $primaryTag ),
			'buttonLabel' => $followLabel !== '' ? $followLabel : 'Follow',
		];
	}

	public function buildProfileUrl( string $tag ): string {
		$tag = ltrim( trim( $tag ), '#' );
		if ( $tag === '' ) {
			return '';
		}

		return 'https://www.instagram.com/explore/tags/' . rawurlencode( $tag ) . '/';
	}
}
