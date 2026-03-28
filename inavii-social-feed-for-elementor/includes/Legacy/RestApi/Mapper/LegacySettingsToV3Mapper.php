<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\RestApi\Mapper;

use Inavii\Instagram\Includes\Legacy\Migration\LegacyFeedLayoutMap;
use Inavii\Instagram\Includes\Legacy\PostTypes\Media\MediaPostType as LegacyMediaPostType;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class LegacySettingsToV3Mapper {
	private ?MediaRepository $mediaRepository;

	public function __construct( ?MediaRepository $mediaRepository = null ) {
		$this->mediaRepository = $mediaRepository;
	}

	/**
	 * Convert legacy V1 feed settings payload (flat filter keys) into V3 settings shape.
	 *
	 * @param array $legacySettings
	 *
	 * @return array
	 */
	public function map( array $legacySettings ): array {
		return $this->mapInternal( $legacySettings, false );
	}

	/**
	 * Enrich legacy feed settings with V3-compatible keys without removing legacy settings.
	 *
	 * @param array $legacySettings
	 *
	 * @return array
	 */
	public function mapForMigration( array $legacySettings ): array {
		return $this->mapInternal( $legacySettings, true );
	}

	/**
	 * @param array $legacySettings
	 */
	private function mapInternal( array $legacySettings, bool $preserveLegacyKeys ): array {
		$mapped  = $legacySettings;
		$filters = isset( $legacySettings['filters'] ) && is_array( $legacySettings['filters'] ) ? $legacySettings['filters'] : [];
		$source  = isset( $legacySettings['source'] ) && is_array( $legacySettings['source'] ) ? $legacySettings['source'] : [];

		$mapped['source'] = [
			'accounts' => $this->normalizeIntList( $source['accounts'] ?? [] ),
			'tagged'   => $this->normalizeIntList( $source['tagged'] ?? [] ),
			'hashtags' => isset( $source['hashtags'] ) && is_array( $source['hashtags'] ) ? array_values( $source['hashtags'] ) : [],
		];

		$design     = isset( $legacySettings['design'] ) && is_array( $legacySettings['design'] ) ? $legacySettings['design'] : [];
		$feedLayout = isset( $design['feedLayout'] ) && is_array( $design['feedLayout'] ) ? $design['feedLayout'] : [];
		$layout     = isset( $legacySettings['layout'] ) && is_scalar( $legacySettings['layout'] ) ? (string) $legacySettings['layout'] : '';
		$mappedLayout = LegacyFeedLayoutMap::toV3( $layout );
		if ( $mappedLayout !== null ) {
			$design['feedLayout'] = array_merge( $feedLayout, $mappedLayout );
		}

		if ( $design !== [] ) {
			$mapped['design'] = $design;
		}

		if ( array_key_exists( 'postOrder', $legacySettings ) ) {
			$filters['orderBy'] = $this->normalizeLegacyPostOrder( $legacySettings['postOrder'] );
		}

		if ( array_key_exists( 'typesOfPosts', $legacySettings ) && is_array( $legacySettings['typesOfPosts'] ) ) {
			$filters['typesOfPosts'] = array_values( $legacySettings['typesOfPosts'] );
		}

		if ( array_key_exists( 'captionFilter', $legacySettings ) && is_array( $legacySettings['captionFilter'] ) ) {
			$filters['captionFilter'] = $this->normalizeIncludeExclude( $legacySettings['captionFilter'] );
		}

		$legacyHashtagFilter = [];
		if ( array_key_exists( 'hashTagFilter', $legacySettings ) && is_array( $legacySettings['hashTagFilter'] ) ) {
			$legacyHashtagFilter = $legacySettings['hashTagFilter'];
		} elseif ( array_key_exists( 'hashtagFilter', $legacySettings ) && is_array( $legacySettings['hashtagFilter'] ) ) {
			$legacyHashtagFilter = $legacySettings['hashtagFilter'];
		}
		if ( $legacyHashtagFilter !== [] ) {
			$filters['hashtagFilter'] = $this->normalizeIncludeExclude( $legacyHashtagFilter );
		}

		if (
			array_key_exists( 'moderateHidePost', $legacySettings )
			|| array_key_exists( 'moderationMode', $legacySettings )
			|| array_key_exists( 'moderation', $legacySettings )
		) {
			$enabled       = $this->toBool( $legacySettings['moderateHidePost'] ?? false );
			$legacyMode    = is_scalar( $legacySettings['moderationMode'] ?? null ) ? strtolower( trim( (string) $legacySettings['moderationMode'] ) ) : 'blacklist';
			$selectedState = $legacyMode === 'whitelist' ? 'show' : 'hide';
			$postIds       = $this->normalizeScalarList( $legacySettings['moderation'] ?? [] );

			$filters['moderationEnabled']   = $enabled;
			$filters['moderationMode']      = $selectedState;
			$filters['moderationSelection'] = [
				'selectedState' => $selectedState,
				'postIds'       => $this->resolveModerationPostIds( $postIds ),
			];
		}

		if (
			array_key_exists( 'promotion', $legacySettings )
			|| array_key_exists( 'promotionsData', $legacySettings )
		) {
			$customLinks = $this->normalizeLegacyPromotions( $legacySettings['promotionsData'] ?? [] );
			$hasConfig   = $customLinks['byPostId'] !== [];

			$filters['customLinksEnabled'] = $this->toBool( $legacySettings['promotion'] ?? false ) || $hasConfig;
			$filters['customLinks']        = $customLinks;
		}

		$mapped['filters'] = $filters;

		if ( ! $preserveLegacyKeys ) {
			unset(
				$mapped['postOrder'],
				$mapped['typesOfPosts'],
				$mapped['captionFilter'],
				$mapped['hashTagFilter'],
				$mapped['hashtagFilter'],
				$mapped['moderateHidePost'],
				$mapped['moderationMode'],
				$mapped['moderation'],
				$mapped['promotion'],
				$mapped['promotionsData']
			);
		}

		return $mapped;
	}

	/**
	 * @param mixed $value
	 */
	private function normalizeLegacyPostOrder( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return 'recent';
		}

		$value = strtolower( trim( (string) $value ) );

		if ( in_array( $value, [ 'likes', 'likecount' ], true ) ) {
			return 'likes';
		}
		if ( in_array( $value, [ 'comments', 'commentcount' ], true ) ) {
			return 'comments';
		}
		if ( in_array( $value, [ 'oldest', 'oldestfirst' ], true ) ) {
			return 'oldest';
		}
		if ( $value === 'random' ) {
			return 'random';
		}

		return 'recent';
	}

	/**
	 * @param array $value
	 *
	 * @return array{include: array, exclude: array}
	 */
	private function normalizeIncludeExclude( array $value ): array {
		$include = isset( $value['include'] ) && is_array( $value['include'] ) ? array_values( $value['include'] ) : [];
		$exclude = isset( $value['exclude'] ) && is_array( $value['exclude'] ) ? array_values( $value['exclude'] ) : [];

		return [
			'include' => $include,
			'exclude' => $exclude,
		];
	}

	/**
	 * @param mixed $items
	 *
	 * @return int[]
	 */
	private function normalizeIntList( $items ): array {
		if ( ! is_array( $items ) ) {
			return [];
		}

		$ids = array_map( 'absint', $items );
		$ids = array_filter(
			$ids,
			static function ( int $id ): bool {
				return $id > 0;
			}
		);

		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param mixed $items
	 *
	 * @return string[]
	 */
	private function normalizeScalarList( $items ): array {
		if ( ! is_array( $items ) ) {
			return [];
		}

		$out = [];
		foreach ( $items as $item ) {
			if ( ! is_scalar( $item ) ) {
				continue;
			}

			$item = trim( (string) $item );
			if ( $item !== '' ) {
				$out[] = $item;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param string[] $legacyIds
	 *
	 * @return string[]
	 */
	private function resolveModerationPostIds( array $legacyIds ): array {
		if ( $legacyIds === [] ) {
			return [];
		}

		$numericIds = [];
		foreach ( $legacyIds as $id ) {
			if ( ctype_digit( $id ) ) {
				$intId = (int) $id;
				if ( $intId > 0 ) {
					$numericIds[] = $intId;
				}
			}
		}

		$numericIds = array_values( array_unique( $numericIds ) );
		$igByNumeric = [];
		$mediaRepository = $this->resolveMediaRepository();

		if ( $numericIds !== [] && $mediaRepository !== null ) {
			$rows = $mediaRepository->posts()->getByIds( $numericIds );
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
				$ig = isset( $row['ig_media_id'] ) && is_scalar( $row['ig_media_id'] ) ? trim( (string) $row['ig_media_id'] ) : '';
				if ( $id > 0 && $ig !== '' ) {
					$igByNumeric[ $id ] = $ig;
				}
			}
		}

		$out = [];
		foreach ( $legacyIds as $id ) {
			if ( ! ctype_digit( $id ) ) {
				$out[] = $id;
				continue;
			}

			$intId = (int) $id;
			if ( $intId <= 0 ) {
				continue;
			}

			if ( isset( $igByNumeric[ $intId ] ) ) {
				$out[] = $igByNumeric[ $intId ];
				continue;
			}

			$legacyMediaId = $this->resolveLegacyMediaId( $intId );
			if ( $legacyMediaId !== '' ) {
				$out[] = $legacyMediaId;
				continue;
			}

			// Keep original id as last-resort fallback to avoid silently dropping moderation choices.
			$out[] = $id;
		}

		return array_values( array_unique( $out ) );
	}

	private function resolveMediaRepository(): ?MediaRepository {
		if ( $this->mediaRepository instanceof MediaRepository ) {
			return $this->mediaRepository;
		}

		if ( ! function_exists( '\Inavii\Instagram\Di\container' ) ) {
			return null;
		}

		try {
			$container = \Inavii\Instagram\Di\container();
			$resolved  = $container->get( MediaRepository::class );
			if ( $resolved instanceof MediaRepository ) {
				$this->mediaRepository = $resolved;
				return $this->mediaRepository;
			}
		} catch ( \Throwable $e ) {
			return null;
		}

		return null;
	}

	private function resolveLegacyMediaId( int $postId ): string {
		if ( ! function_exists( 'get_post_meta' ) ) {
			return '';
		}

		$mediaId = get_post_meta( $postId, LegacyMediaPostType::MEDIA_ID, true );
		if ( ! is_scalar( $mediaId ) ) {
			return '';
		}

		return trim( (string) $mediaId );
	}

	/**
	 * @param mixed $value
	 *
	 * @return array{selectedPostId: string|null, byPostId: array<string,array<string,string>>}
	 */
	private function normalizeLegacyPromotions( $value ): array {
		if ( ! is_array( $value ) ) {
			return [
				'selectedPostId' => null,
				'byPostId'       => [],
			];
		}

		$byPostId = [];

		foreach ( $value as $rawPostId => $rawConfig ) {
			if ( ! is_scalar( $rawPostId ) || ! is_array( $rawConfig ) ) {
				continue;
			}

			$postId = $this->resolvePromotionPostId( (string) $rawPostId );
			if ( $postId === '' ) {
				continue;
			}

			$config = $this->normalizeLegacyPromotionConfig( $rawConfig );
			if ( $config === [] ) {
				continue;
			}

			$byPostId[ $postId ] = $config;
		}

		$selectedPostId = $byPostId !== [] ? (string) array_key_first( $byPostId ) : null;

		return [
			'selectedPostId' => $selectedPostId,
			'byPostId'       => $byPostId,
		];
	}

	private function resolvePromotionPostId( string $legacyPostId ): string {
		$legacyPostId = trim( $legacyPostId );
		if ( $legacyPostId === '' ) {
			return '';
		}

		$resolved = $this->resolveModerationPostIds( [ $legacyPostId ] );

		return isset( $resolved[0] ) && is_string( $resolved[0] ) ? $resolved[0] : '';
	}

	/**
	 * @param array $legacyConfig
	 *
	 * @return array<string,string>
	 */
	private function normalizeLegacyPromotionConfig( array $legacyConfig ): array {
		$out = [];

		$linkSource = isset( $legacyConfig['linkSource'] ) && is_scalar( $legacyConfig['linkSource'] )
			? strtolower( trim( (string) $legacyConfig['linkSource'] ) )
			: '';

		$linkUrl = isset( $legacyConfig['linkUrl'] ) && is_scalar( $legacyConfig['linkUrl'] )
			? trim( (string) $legacyConfig['linkUrl'] )
			: '';

		if ( $linkSource !== 'instagram' && $linkUrl !== '' ) {
			$out['linkUrl'] = $linkUrl;
		}

		$buttonText = isset( $legacyConfig['buttonModalTitle'] ) && is_scalar( $legacyConfig['buttonModalTitle'] )
			? trim( (string) $legacyConfig['buttonModalTitle'] )
			: '';

		if ( $buttonText !== '' ) {
			$out['buttonText'] = $buttonText;
		}

		$target = isset( $legacyConfig['target'] ) && is_scalar( $legacyConfig['target'] )
			? strtolower( trim( (string) $legacyConfig['target'] ) )
			: '';

		if ( $target === '_self' || $target === 'self' ) {
			$out['openMode'] = 'same';
		} elseif ( $target !== '' ) {
			$out['openMode'] = 'new';
		}

		return $out;
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
