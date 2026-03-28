<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Front;

final class LegacyMediaViewMapper {
	/**
	 * @param array $posts
	 *
	 * @return array
	 */
	public function mapPosts( array $posts ): array {
		if ( $posts === [] ) {
			return [];
		}

		$mapped = [];
		foreach ( $posts as $post ) {
			if ( ! is_array( $post ) ) {
				continue;
			}

			$mapped[] = $this->mapPost( $post );
		}

		return $mapped;
	}

	/**
	 * @param array $post
	 *
	 * @return array
	 */
	private function mapPost( array $post ): array {
		$likeCount     = isset( $post['likeCount'] ) ? (int) $post['likeCount'] : (int) ( $post['likesCount'] ?? 0 );
		$commentsCount = isset( $post['commentsCount'] ) ? (int) $post['commentsCount'] : 0;
		$caption       = isset( $post['caption'] ) ? (string) $post['caption'] : '';
		$mediaUrl      = $this->normalizeMediaUrl( $post );

		$post['date']                        = isset( $post['date'] ) && is_string( $post['date'] ) ? $post['date'] : (string) ( $post['postedAt'] ?? '' );
		$post['likeCount']                   = $likeCount;
		$post['commentsCount']               = $commentsCount;
		$post['caption__premium_only']       = esc_html( $caption );
		$post['likeCount__premium_only']     = $likeCount;
		$post['commentsCount__premium_only'] = $commentsCount;
		$post['promotion__premium_only']     = isset( $post['promotion__premium_only'] ) && is_array( $post['promotion__premium_only'] )
			? $post['promotion__premium_only']
			: ( isset( $post['promotion'] ) && is_array( $post['promotion'] ) ? $post['promotion'] : [] );
		$post['mediaUrl']                    = $mediaUrl;
		$post['url']                         = $this->readString( $post, 'url' );
		if ( $post['url'] === '' ) {
			$post['url'] = $mediaUrl['full'];
		}

		$post['alternativeUrl'] = $this->resolveAlternativeUrl( $post );
		if ( $post['alternativeUrl'] === '' ) {
			$post['alternativeUrl'] = $mediaUrl['full'];
		}

		$post['children']                    = $this->mapChildren( $post['children'] ?? [] );

		if ( ! isset( $post['name'] ) || ! is_string( $post['name'] ) ) {
			$post['name'] = '';
		}

		return $post;
	}

	/**
	 * @param mixed $children
	 *
	 * @return array
	 */
	private function mapChildren( $children ): array {
		if ( ! is_array( $children ) ) {
			return [];
		}

		$mapped = [];
		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$child['mediaUrl'] = $this->normalizeMediaUrl( $child );
			$child['url']      = $this->readString( $child, 'url' );
			if ( $child['url'] === '' ) {
				$child['url'] = $this->resolveChildUrl( $child );
			}

			$child['alternativeUrl'] = $this->readString( $child, 'alternativeUrl' );
			if ( $child['alternativeUrl'] === '' ) {
				$child['alternativeUrl'] = $child['url'];
			}

			$mapped[] = $child;
		}

		return $mapped;
	}

	/**
	 * @param array $post
	 */
	private function resolveAlternativeUrl( array $post ): string {
		if ( isset( $post['alternativeUrl'] ) && is_string( $post['alternativeUrl'] ) ) {
			return $post['alternativeUrl'];
		}

		if ( isset( $post['media'] ) && is_array( $post['media'] ) && isset( $post['media']['url'] ) && is_string( $post['media']['url'] ) ) {
			return $post['media']['url'];
		}

		$mediaUrl = $post['mediaUrl'] ?? null;
		if ( is_array( $mediaUrl ) ) {
			if ( isset( $mediaUrl['large'] ) && is_string( $mediaUrl['large'] ) ) {
				return $mediaUrl['large'];
			}
			if ( isset( $mediaUrl['thumbnail'] ) && is_string( $mediaUrl['thumbnail'] ) ) {
				return $mediaUrl['thumbnail'];
			}
			if ( isset( $mediaUrl['medium'] ) && is_string( $mediaUrl['medium'] ) ) {
				return $mediaUrl['medium'];
			}
		}

		return '';
	}

	/**
	 * @param array $child
	 */
	private function resolveChildUrl( array $child ): string {
		$mediaUrl = $this->normalizeMediaUrl( $child );

		return $mediaUrl['full'];
	}

	/**
	 * @param array $data
	 *
	 * @return array{full:string,small:string,medium:string,large:string,thumbnail:string}
	 */
	private function normalizeMediaUrl( array $data ): array {
		$thumbnail = $this->firstNonEmpty(
			$this->readMediaUrlKey( $data, 'thumbnail' ),
			$this->readMediaUrlKey( $data, 'medium' ),
			$this->readMediaUrlKey( $data, 'small' )
		);

		$full = $this->firstNonEmpty(
			$this->readMediaUrlKey( $data, 'full' ),
			$this->readMediaUrlKey( $data, 'large' ),
			$this->readNestedString( $data, 'media', 'url' ),
			$this->readString( $data, 'alternativeUrl' ),
			$this->readString( $data, 'url' ),
			$thumbnail
		);

		if ( $thumbnail === '' ) {
			$thumbnail = $full;
		}

		$small = $this->firstNonEmpty(
			$this->readMediaUrlKey( $data, 'small' ),
			$this->readMediaUrlKey( $data, 'thumbnail' ),
			$this->readMediaUrlKey( $data, 'medium' ),
			$thumbnail,
			$full
		);

		$medium = $this->firstNonEmpty(
			$this->readMediaUrlKey( $data, 'medium' ),
			$this->readMediaUrlKey( $data, 'thumbnail' ),
			$this->readMediaUrlKey( $data, 'small' ),
			$thumbnail,
			$full
		);

		$large = $this->firstNonEmpty(
			$this->readMediaUrlKey( $data, 'large' ),
			$this->readMediaUrlKey( $data, 'full' ),
			$full,
			$medium
		);

		return [
			'full'      => $full,
			'small'     => $small,
			'medium'    => $medium,
			'large'     => $large,
			'thumbnail' => $thumbnail,
		];
	}

	private function readMediaUrlKey( array $data, string $key ): string {
		$mediaUrl = $data['mediaUrl'] ?? null;
		if ( is_array( $mediaUrl ) && isset( $mediaUrl[ $key ] ) && is_string( $mediaUrl[ $key ] ) ) {
			return $mediaUrl[ $key ];
		}

		if ( is_string( $mediaUrl ) ) {
			return $mediaUrl;
		}

		return '';
	}

	private function readNestedString( array $data, string $parent, string $key ): string {
		$value = $data[ $parent ] ?? null;
		if ( ! is_array( $value ) ) {
			return '';
		}

		return isset( $value[ $key ] ) && is_string( $value[ $key ] ) ? $value[ $key ] : '';
	}

	private function readString( array $data, string $key ): string {
		return isset( $data[ $key ] ) && is_string( $data[ $key ] ) ? $data[ $key ] : '';
	}

	private function firstNonEmpty( string ...$values ): string {
		foreach ( $values as $value ) {
			if ( trim( $value ) !== '' ) {
				return $value;
			}
		}

		return '';
	}
}
