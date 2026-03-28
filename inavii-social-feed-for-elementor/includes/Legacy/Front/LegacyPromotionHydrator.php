<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Front;

use Inavii\Instagram\Includes\Legacy\PostTypes\Media\MediaPostType as LegacyMediaPostType;

final class LegacyPromotionHydrator {
	public function isPromotionEnabled( array $feedSettings ): bool {
		if ( isset( $feedSettings['promotion'] ) && $this->toBool( $feedSettings['promotion'] ) ) {
			return true;
		}

		$legacyPromotions = $feedSettings['promotionsData'] ?? [];
		if ( is_array( $legacyPromotions ) && $legacyPromotions !== [] ) {
			return true;
		}

		$filters = isset( $feedSettings['filters'] ) && is_array( $feedSettings['filters'] ) ? $feedSettings['filters'] : [];
		if ( $this->toBool( $filters['customLinksEnabled'] ?? false ) ) {
			return true;
		}

		$customLinks = isset( $filters['customLinks'] ) && is_array( $filters['customLinks'] ) ? $filters['customLinks'] : [];
		$byPostId    = isset( $customLinks['byPostId'] ) && is_array( $customLinks['byPostId'] ) ? $customLinks['byPostId'] : [];

		return $byPostId !== [];
	}

	/**
	 * @param array $posts
	 * @param array $feedSettings
	 *
	 * @return array
	 */
	public function apply( array $posts, array $feedSettings ): array {
		if ( $posts === [] ) {
			return [];
		}

		$promotionMap = $this->resolvePromotionMap( $feedSettings );
		if ( $promotionMap === [] ) {
			return $posts;
		}

		$hydrated = [];
		foreach ( $posts as $post ) {
			if ( ! is_array( $post ) ) {
				$hydrated[] = $post;
				continue;
			}

			$promotion = $this->resolvePostPromotion( $post, $promotionMap );
			if ( $promotion !== null ) {
				$post['promotion']               = $promotion;
				$post['promotion__premium_only'] = $promotion;
			}

			$hydrated[] = $post;
		}

		return $hydrated;
	}

	/**
	 * @param array $feedSettings
	 *
	 * @return array<string,array<string,string>>
	 */
	private function resolvePromotionMap( array $feedSettings ): array {
		$legacyPromotions = $this->normalizeLegacyPromotions( $feedSettings['promotionsData'] ?? [] );
		$filters          = isset( $feedSettings['filters'] ) && is_array( $feedSettings['filters'] ) ? $feedSettings['filters'] : [];
		$v3Promotions     = $this->normalizeV3CustomLinks( $filters['customLinks'] ?? [] );

		return array_replace( $v3Promotions, $legacyPromotions );
	}

	/**
	 * @param array<string,array<string,string>> $promotionMap
	 */
	private function resolvePostPromotion( array $post, array $promotionMap ): ?array {
		foreach ( $this->candidatePostIds( $post ) as $postId ) {
			if ( isset( $promotionMap[ $postId ] ) ) {
				return $promotionMap[ $postId ];
			}
		}

		return null;
	}

	/**
	 * @param array $post
	 *
	 * @return string[]
	 */
	private function candidatePostIds( array $post ): array {
		$ids = [];

		foreach ( [ 'mediaId', 'ig_media_id', 'id' ] as $key ) {
			if ( ! isset( $post[ $key ] ) || ! is_scalar( $post[ $key ] ) ) {
				continue;
			}

			$id = trim( (string) $post[ $key ] );
			if ( $id !== '' ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param mixed $value
	 *
	 * @return array<string,array<string,string>>
	 */
	private function normalizeLegacyPromotions( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$out = [];
		foreach ( $value as $rawPostId => $rawConfig ) {
			if ( ! is_scalar( $rawPostId ) || ! is_array( $rawConfig ) ) {
				continue;
			}

			$postId = trim( (string) $rawPostId );
			if ( $postId === '' ) {
				continue;
			}

			$config = $this->normalizeLegacyPromotionConfig( $rawConfig );
			if ( $config === [] ) {
				continue;
			}

			$out[ $postId ] = $config;

			$resolvedMediaId = $this->resolveLegacyPromotionMediaId( $postId );
			if ( $resolvedMediaId !== '' ) {
				$out[ $resolvedMediaId ] = $config;
			}
		}

		return $out;
	}

	/**
	 * @param mixed $value
	 *
	 * @return array<string,array<string,string>>
	 */
	private function normalizeV3CustomLinks( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$byPostId = isset( $value['byPostId'] ) && is_array( $value['byPostId'] ) ? $value['byPostId'] : [];
		if ( $byPostId === [] ) {
			return [];
		}

		$out = [];
		foreach ( $byPostId as $rawPostId => $rawConfig ) {
			if ( ! is_scalar( $rawPostId ) || ! is_array( $rawConfig ) ) {
				continue;
			}

			$postId = trim( (string) $rawPostId );
			if ( $postId === '' ) {
				continue;
			}

			$config = $this->normalizeV3PromotionConfig( $rawConfig );
			if ( $config === [] ) {
				continue;
			}

			$out[ $postId ] = $config;
		}

		return $out;
	}

	/**
	 * @param array $config
	 *
	 * @return array<string,string>
	 */
	private function normalizeLegacyPromotionConfig( array $config ): array {
		$linkSource = isset( $config['linkSource'] ) && is_scalar( $config['linkSource'] )
			? strtolower( trim( (string) $config['linkSource'] ) )
			: '';
		$linkUrl = isset( $config['linkUrl'] ) && is_scalar( $config['linkUrl'] )
			? trim( (string) $config['linkUrl'] )
			: '';
		$target = isset( $config['target'] ) && is_scalar( $config['target'] )
			? trim( (string) $config['target'] )
			: '';
		$buttonModalTitle = isset( $config['buttonModalTitle'] ) && is_scalar( $config['buttonModalTitle'] )
			? trim( (string) $config['buttonModalTitle'] )
			: '';

		if ( $linkSource === '' ) {
			$linkSource = $linkUrl !== '' ? 'custom' : 'instagram';
		}
		if ( $target === '' ) {
			$target = '_blank';
		}

		if ( $linkUrl === '' && $buttonModalTitle === '' && $target === '_blank' && $linkSource === 'instagram' ) {
			return [];
		}

		return [
			'linkSource'       => $linkSource,
			'linkUrl'          => $linkSource !== 'instagram' ? $linkUrl : '',
			'target'           => $target,
			'buttonModalTitle' => $buttonModalTitle,
		];
	}

	/**
	 * @param array $config
	 *
	 * @return array<string,string>
	 */
	private function normalizeV3PromotionConfig( array $config ): array {
		$linkUrl = isset( $config['linkUrl'] ) && is_scalar( $config['linkUrl'] )
			? trim( (string) $config['linkUrl'] )
			: '';
		$buttonModalTitle = isset( $config['buttonText'] ) && is_scalar( $config['buttonText'] )
			? trim( (string) $config['buttonText'] )
			: '';
		$openMode = isset( $config['openMode'] ) && is_scalar( $config['openMode'] )
			? strtolower( trim( (string) $config['openMode'] ) )
			: 'new';

		if ( $linkUrl === '' && $buttonModalTitle === '' ) {
			return [];
		}

		return [
			'linkSource'       => $linkUrl !== '' ? 'custom' : 'instagram',
			'linkUrl'          => $linkUrl,
			'target'           => $openMode === 'same' ? '_self' : '_blank',
			'buttonModalTitle' => $buttonModalTitle,
		];
	}

	private function resolveLegacyPromotionMediaId( string $postId ): string {
		if ( ! ctype_digit( $postId ) || ! function_exists( 'get_post_meta' ) ) {
			return '';
		}

		$mediaId = get_post_meta( (int) $postId, LegacyMediaPostType::MEDIA_ID, true );
		if ( ! is_scalar( $mediaId ) ) {
			return '';
		}

		return trim( (string) $mediaId );
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
}
