<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Domain;

use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;

final class FeedSettings {
	private const DEFAULT_HASHTAG_TYPE = 'top_media';
	private const FREE_MOBILE_FRIENDLY_MODE = 'popup';

	/** @var array<string,array{view:string,viewVariant:string}> */
	private const PRO_LAYOUT_FALLBACKS = [
		'grid_cards'          => [
			'view'        => 'grid',
			'viewVariant' => 'grid',
		],
		'highlight_discovery' => [
			'view'        => 'highlight',
			'viewVariant' => 'highlight',
		],
		'highlight_super'     => [
			'view'        => 'highlight',
			'viewVariant' => 'highlight',
		],
		'masonry_cards'       => [
			'view'        => 'masonry',
			'viewVariant' => 'masonry',
		],
		'slider_cards'        => [
			'view'        => 'slider',
			'viewVariant' => 'slider',
		],
		'showcase'            => [
			'view'        => 'slider',
			'viewVariant' => 'slider',
		],
		'infinite'            => [
			'view'        => 'slider',
			'viewVariant' => 'slider',
		],
		'sidebar'             => [
			'view'        => 'grid',
			'viewVariant' => 'grid',
		],
	];

	/** @var array<string,string> */
	private const BASE_LAYOUT_BY_MODE = [
		'grid'                => 'grid',
		'grid_cards'          => 'grid',
		'grid_wave'           => 'grid',
		'grid_wave_grid'      => 'grid',
		'grid_row'            => 'grid',
		'grid_gallery'        => 'grid',
		'highlight'           => 'highlight',
		'highlight_discovery' => 'highlight',
		'highlight_super'     => 'highlight',
		'masonry'             => 'masonry',
		'masonry_cards'       => 'masonry',
		'slider'              => 'slider',
		'slider_cards'        => 'slider',
		'showcase'            => 'slider',
		'infinite'            => 'slider',
		'sidebar'             => 'sidebar',
	];

	private FeedSources $sources;
	/** @var array */
	private array $raw;

	public function __construct( FeedSources $sources, array $raw ) {
		$this->sources = $sources;
		$this->raw     = $raw;
	}

	/**
	 * Accept new React settings shape and keep backwards compatibility with older feed settings.
	 *
	 * @param array $data Feed settings payload.
	 */
	public static function fromArray( array $data, ?ProFeaturesPolicy $proFeatures = null ): self {
		$proFeatures = $proFeatures ?? new ProFeaturesPolicy();
		$normalized = $data;

		$sourceInput = [];
		if ( isset( $data['source'] ) && is_array( $data['source'] ) ) {
			$sourceInput = $data['source'];
		}

		$hashtagsInput = [];
		if ( isset( $data['hashtagConfig'] ) && is_array( $data['hashtagConfig'] ) ) {
			$hashtagsInput = $data['hashtagConfig'];
		} elseif ( isset( $sourceInput['hashtags'] ) && is_array( $sourceInput['hashtags'] ) ) {
			$hashtagsInput = $sourceInput['hashtags'];
		}

		$hashtagConfig = $proFeatures->canUseHashtagSources()
			? self::normalizeHashtagConfig( $hashtagsInput )
			: [];
		if ( $hashtagConfig !== [] ) {
			$sourceInput['hashtags'] = array_column( $hashtagConfig, 'id' );
		}

		$sources            = FeedSources::fromArray( $sourceInput, $proFeatures );
		$filters            = isset( $data['filters'] ) && is_array( $data['filters'] ) ? $data['filters'] : [];
		$filters            = array_merge( $filters, FeedMediaFilters::fromArray( $filters, $proFeatures )->toArray() );
		$filters            = self::normalizeCustomLinks( $filters, $proFeatures );
		$rawFeedMode        = isset( $data['feedMode'] ) && is_array( $data['feedMode'] ) ? $data['feedMode'] : [];
		$storedFeedMode     = $proFeatures->sanitizeFeedMode( (string) ( $rawFeedMode['mode'] ?? '' ) );

		$normalized['source']        = $sources->toArray();
		$normalized['filters']       = $filters;
		$normalized['feedMode']      = self::normalizeFeedModeSettings( $rawFeedMode, $proFeatures, $storedFeedMode );
		$normalized['design']        = self::normalizeDesign(
			isset( $data['design'] ) && is_array( $data['design'] ) ? $data['design'] : [],
			$proFeatures,
			$storedFeedMode
		);
		$normalized['hashtagConfig'] = $proFeatures->canUseHashtagSources()
			? self::normalizeHashtagConfig( $hashtagConfig, $sources->hashtags() )
			: [];

		unset( $normalized['availablePosts'] );

		return new self( $sources, $normalized );
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return $this->raw;
	}

	public function source(): FeedSources {
		return $this->sources;
	}

	public function sources(): FeedSources {
		return $this->source();
	}

	/**
	 * @return array
	 */
	public function filters(): array {
		$filters = $this->raw['filters'] ?? [];

		return is_array( $filters ) ? $filters : [];
	}

	public function mediaFilters(): FeedMediaFilters {
		return FeedMediaFilters::fromArray( $this->filters() );
	}

	/**
	 * @return array
	 */
	public function design(): array {
		$design = $this->raw['design'] ?? [];

		return is_array( $design ) ? $design : [];
	}

	public function globalDisplayEnabled(): bool {
		$feedMode = $this->raw['feedMode'] ?? null;
		if ( ! is_array( $feedMode ) || ! array_key_exists( 'globalEnabled', $feedMode ) ) {
			return false;
		}

		return $this->toBoolean( $feedMode['globalEnabled'] );
	}

	/**
	 * @return array
	 */
	public function hashtagConfig(): array {
		$items = $this->raw['hashtagConfig'] ?? [];

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @param mixed[]  $items List of hashtag config rows.
	 * @param string[] $fallbackHashtags Fallback hashtag ids from source.
	 *
	 * @return array
	 */
	private static function normalizeHashtagConfig( array $items, array $fallbackHashtags = [] ): array {
		$out = [];

		foreach ( $items as $item ) {
			$tag  = '';
			$type = self::DEFAULT_HASHTAG_TYPE;

			if ( is_array( $item ) ) {
				if ( isset( $item['id'] ) && is_scalar( $item['id'] ) ) {
					$tag = (string) $item['id'];
				} elseif ( isset( $item['name'] ) && is_scalar( $item['name'] ) ) {
					$tag = (string) $item['name'];
				}

				if ( isset( $item['type'] ) && is_scalar( $item['type'] ) ) {
					$type = self::normalizeHashtagType( (string) $item['type'] );
				}
			} elseif ( is_scalar( $item ) ) {
				$tag = (string) $item;
			}

			$tag = self::normalizeHashtagId( $tag );
			if ( $tag === '' ) {
				continue;
			}

			$out[ $tag ] = [
				'id'   => $tag,
				'type' => $type,
			];
		}

		foreach ( $fallbackHashtags as $tag ) {
			$tag = self::normalizeHashtagId( $tag );
			if ( $tag === '' || isset( $out[ $tag ] ) ) {
				continue;
			}

			$out[ $tag ] = [
				'id'   => $tag,
				'type' => self::DEFAULT_HASHTAG_TYPE,
			];
		}

		return array_values( $out );
	}

	private static function normalizeHashtagId( string $tag ): string {
		$tag = trim( $tag );
		if ( $tag === '' ) {
			return '';
		}

		if ( $tag[0] === '#' ) {
			$tag = substr( $tag, 1 );
		}

		return strtolower( trim( $tag ) );
	}

	private static function normalizeHashtagType( string $type ): string {
		$type = trim( strtolower( $type ) );
		if ( $type === 'recent_media' ) {
			return 'recent_media';
		}

		return self::DEFAULT_HASHTAG_TYPE;
	}

	/**
	 * @param array $filters
	 *
	 * @return array
	 */
	private static function normalizeCustomLinks( array $filters, ProFeaturesPolicy $proFeatures ): array {
		$enabled          = $proFeatures->canUseCustomLinks() && self::toBool( $filters['customLinksEnabled'] ?? false );
		$customLinksInput = isset( $filters['customLinks'] ) && is_array( $filters['customLinks'] ) ? $filters['customLinks'] : [];

		$filters['customLinksEnabled'] = $enabled;
		$filters['customLinks']        = [
			'selectedPostId' => self::normalizeCustomLinksPostId( $customLinksInput['selectedPostId'] ?? null ),
			'byPostId'       => $enabled ? self::normalizeCustomLinksMap( $customLinksInput['byPostId'] ?? [] ) : [],
		];

		return $filters;
	}

	/**
	 * @param array $feedMode
	 *
	 * @return array
	 */
	private static function normalizeFeedModeSettings( array $feedMode, ProFeaturesPolicy $proFeatures, string $storedFeedMode ): array {
		$feedMode['mode']          = $storedFeedMode;
		$feedMode['globalEnabled'] = $storedFeedMode === 'offcanvas'
			&& $proFeatures->canUseGlobalFeedVisibility()
			&& self::toBool( $feedMode['globalEnabled'] ?? false );

		return $feedMode;
	}

	/**
	 * @param array $design
	 *
	 * @return array
	 */
	private static function normalizeDesign( array $design, ProFeaturesPolicy $proFeatures, string $storedFeedMode ): array {
		$design['feedLayout']     = self::normalizeFeedLayout(
			isset( $design['feedLayout'] ) && is_array( $design['feedLayout'] ) ? $design['feedLayout'] : [],
			$proFeatures,
			$storedFeedMode
		);
		$design['clickAction']    = self::normalizeClickAction(
			isset( $design['clickAction'] ) && is_array( $design['clickAction'] ) ? $design['clickAction'] : [],
			$proFeatures
		);
		$design['mobileFriendly'] = self::normalizeMobileFriendly(
			isset( $design['mobileFriendly'] ) && is_array( $design['mobileFriendly'] ) ? $design['mobileFriendly'] : [],
			$proFeatures
		);

		return $design;
	}

	/**
	 * @param array $feedLayout
	 *
	 * @return array
	 */
	private static function normalizeFeedLayout( array $feedLayout, ProFeaturesPolicy $proFeatures, string $storedFeedMode ): array {
		if ( $storedFeedMode === 'offcanvas' ) {
			$feedLayout['view']        = 'sidebar';
			$feedLayout['viewVariant'] = 'sidebar';

			return $feedLayout;
		}

		$view     = self::normalizeLayoutMode( $feedLayout['view'] ?? null );
		$variant  = self::normalizeLayoutMode( $feedLayout['viewVariant'] ?? null );
		$selected = $variant !== '' ? $variant : $view;

		if ( $selected === '' ) {
			$feedLayout['view']        = 'grid';
			$feedLayout['viewVariant'] = 'grid';

			return $feedLayout;
		}

		if ( ! $proFeatures->canUseLayoutVariant( $view, $selected ) ) {
			$fallback                  = self::PRO_LAYOUT_FALLBACKS[ $selected ] ?? self::PRO_LAYOUT_FALLBACKS['sidebar'];
			$feedLayout['view']        = $fallback['view'];
			$feedLayout['viewVariant'] = $fallback['viewVariant'];

			return $feedLayout;
		}

		$feedLayout['view']        = self::BASE_LAYOUT_BY_MODE[ $selected ] ?? 'grid';
		$feedLayout['viewVariant'] = $selected;

		return $feedLayout;
	}

	/**
	 * @param array $clickAction
	 *
	 * @return array
	 */
	private static function normalizeClickAction( array $clickAction, ProFeaturesPolicy $proFeatures ): array {
		$action = self::normalizeImageClickAction( $clickAction['imageClickAction'] ?? null );
		if ( $action !== '' && ! $proFeatures->canUseImageClickAction( $action ) ) {
			$action = 'popup';
		}

		if ( $action !== '' ) {
			$clickAction['imageClickAction'] = $action;
		}

		$instagramOpenMode = self::normalizeOpenMode( $clickAction['instagramOpenMode'] ?? null );
		if ( $instagramOpenMode !== null ) {
			$clickAction['instagramOpenMode'] = $instagramOpenMode;
		}

		return $clickAction;
	}

	/**
	 * @param array $mobileFriendly
	 *
	 * @return array
	 */
	private static function normalizeMobileFriendly( array $mobileFriendly, ProFeaturesPolicy $proFeatures ): array {
		$mode = self::normalizeMobileFriendlyMode( $mobileFriendly['mode'] ?? null );
		if ( $mode === '' ) {
			$mobileFriendly['mode'] = self::FREE_MOBILE_FRIENDLY_MODE;

			return $mobileFriendly;
		}

		if ( ! $proFeatures->canUseMobileFriendlyMode( $mode ) ) {
			$mode = self::FREE_MOBILE_FRIENDLY_MODE;
		}

		$mobileFriendly['mode'] = $mode;

		return $mobileFriendly;
	}

	private static function normalizeLayoutMode( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return strtolower( trim( (string) $value ) );
	}

	private static function normalizeImageClickAction( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$normalized = strtolower( trim( (string) $value ) );
		if ( $normalized === 'video_box' || $normalized === 'none' ) {
			return 'play_in_grid';
		}

		return $normalized;
	}

	private static function normalizeMobileFriendlyMode( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$normalized = strtolower( trim( (string) $value ) );
		if ( in_array( $normalized, [ 'popup', 'classic_scroll', 'smart_snap' ], true ) ) {
			return $normalized;
		}

		return '';
	}

	/**
	 * @param mixed $value
	 *
	 * @return string|null
	 */
	private static function normalizeCustomLinksPostId( $value ): ?string {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$normalized = trim( (string) $value );

		return $normalized !== '' ? $normalized : null;
	}

	/**
	 * @param mixed $value
	 *
	 * @return array
	 */
	private static function normalizeCustomLinksMap( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$out = [];
		foreach ( $value as $rawPostId => $rawConfig ) {
			if ( ! is_scalar( $rawPostId ) ) {
				continue;
			}

			$postId = trim( (string) $rawPostId );
			if ( $postId === '' || ! is_array( $rawConfig ) ) {
				continue;
			}

			$config = self::normalizeCustomLinksItem( $rawConfig );
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
	 * @return array
	 */
	private static function normalizeCustomLinksItem( array $config ): array {
		$out = [];

		$text = self::trimToNullableString( $config['buttonText'] ?? null );
		if ( $text !== null ) {
			$out['buttonText'] = $text;
		}

		$link = self::normalizeCustomLinkUrl( $config['linkUrl'] ?? null );
		if ( $link !== null ) {
			$out['linkUrl'] = $link;
		}

		$textColor = self::trimToNullableString( $config['textColor'] ?? null );
		if ( $textColor !== null ) {
			$out['textColor'] = $textColor;
		}

		$backgroundColor = self::trimToNullableString( $config['backgroundColor'] ?? null );
		if ( $backgroundColor !== null ) {
			$out['backgroundColor'] = $backgroundColor;
		}

		$openMode = self::normalizeOpenMode( $config['openMode'] ?? null );
		if ( $openMode !== null ) {
			$out['openMode'] = $openMode;
		}

		return $out;
	}

	/**
	 * @param mixed $value
	 *
	 * @return string|null
	 */
	private static function trimToNullableString( $value ): ?string {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$normalized = trim( (string) $value );

		return $normalized !== '' ? $normalized : null;
	}

	/**
	 * Allow only safe web destinations for custom CTA links.
	 *
	 * Supported:
	 * - http / https absolute URLs
	 * - protocol-relative URLs
	 * - site-relative, query and hash targets
	 * - bare domains/paths that frontend will normalize to https
	 *
	 * @param mixed $value
	 *
	 * @return string|null
	 */
	private static function normalizeCustomLinkUrl( $value ): ?string {
		$normalized = self::trimToNullableString( $value );
		if ( $normalized === null ) {
			return null;
		}

		$normalized = preg_replace( '/[\x00-\x1F\x7F]+/u', '', $normalized );
		$normalized = is_string( $normalized ) ? trim( $normalized ) : '';
		if ( $normalized === '' ) {
			return null;
		}

		if (
			strpos( $normalized, '//' ) === 0 ||
			strpos( $normalized, '/' ) === 0 ||
			strpos( $normalized, '#' ) === 0 ||
			strpos( $normalized, '?' ) === 0
		) {
			return $normalized;
		}

		if ( preg_match( '/^[a-zA-Z][a-zA-Z\d+\-.]*:/', $normalized ) === 1 ) {
			$scheme = wp_parse_url( $normalized, PHP_URL_SCHEME );
			$scheme = is_string( $scheme ) ? strtolower( $scheme ) : '';
			if ( $scheme !== 'http' && $scheme !== 'https' ) {
				return null;
			}
		}

		return $normalized;
	}

	/**
	 * @param mixed $value
	 *
	 * @return string|null
	 */
	private static function normalizeOpenMode( $value ): ?string {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$normalized = strtolower( trim( (string) $value ) );
		if ( $normalized === 'same' || $normalized === 'new' ) {
			return $normalized;
		}

		return null;
	}

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	private static function toBool( $value ): bool {
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

	/**
	 * @param mixed $value Scalar or bool value.
	 */
	private function toBoolean( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value === 1;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			return in_array( $value, [ '1', 'true', 'yes', 'on' ], true );
		}

		return false;
	}
}
