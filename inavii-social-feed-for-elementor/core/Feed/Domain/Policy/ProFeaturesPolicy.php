<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Domain\Policy;

use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\Freemius\FreemiusAccess;

final class ProFeaturesPolicy {
	private const FEED_MODE_OFFCANVAS = 'offcanvas';

	/** @var string[] */
	private const PRO_LAYOUT_VARIANTS = [
		'grid_cards',
		'highlight_discovery',
		'highlight_super',
		'masonry_cards',
		'slider_cards',
		'showcase',
		'infinite',
		'sidebar',
	];

	private bool $proEnabled;

	public function __construct( ?bool $proEnabled = null ) {
		$this->proEnabled = $proEnabled ?? $this->resolveProEnabled();
	}

	public function isPro(): bool {
		return $this->proEnabled;
	}

	public function canUseHashtagSources(): bool {
		return $this->isPro();
	}

	public function canUseTaggedPosts(): bool {
		return $this->isPro();
	}

	public function canUseMultipleSourceAccounts(): bool {
		return $this->isPro();
	}

	public function canUseGlobalFeedVisibility(): bool {
		return $this->isPro();
	}

	public function canUseSlideOutPanel(): bool {
		return $this->isPro();
	}

	public function canUseCaptionFiltering(): bool {
		return $this->isPro();
	}

	public function canUseHashtagFiltering(): bool {
		return $this->isPro();
	}

	public function canUseModerationFiltering(): bool {
		return $this->isPro();
	}

	public function canUseCustomLinks(): bool {
		return $this->isPro();
	}

	public function canUseTypesOfPostsFiltering(): bool {
		return $this->isPro();
	}

	public function canUseEngagementSorting(): bool {
		return $this->isPro();
	}

	public function canUseMobileFriendlyMode( string $mode ): bool {
		$mode = strtolower( trim( $mode ) );

		if ( $mode === 'smart_snap' || $mode === 'classic_scroll' ) {
			return $this->isPro();
		}

		return true;
	}

	public function canUseLayoutVariant( string $view, string $variant ): bool {
		$selected = trim( strtolower( $variant !== '' ? $variant : $view ) );
		if ( $selected === '' ) {
			return true;
		}

		if ( in_array( $selected, self::PRO_LAYOUT_VARIANTS, true ) ) {
			return $this->isPro();
		}

		return true;
	}

	public function canUseImageClickAction( string $action ): bool {
		$action = strtolower( trim( $action ) );

		if ( $action === 'lightbox' || $action === 'play_in_grid' || $action === 'video_box' || $action === 'none' ) {
			return $this->isPro();
		}

		return true;
	}

	public function sanitizeFeedMode( string $feedMode ): string {
		$feedMode = strtolower( trim( $feedMode ) );
		if ( $feedMode === '' ) {
			return FeedRepository::DEFAULT_FEED_MODE;
		}

		if ( $feedMode === self::FEED_MODE_OFFCANVAS && ! $this->canUseSlideOutPanel() ) {
			return FeedRepository::DEFAULT_FEED_MODE;
		}

		return $feedMode;
	}

	public function capabilitiesForApi(): array {
		return [
			'isPro'       => $this->isPro(),
			'sources'     => [
				'multipleAccounts' => $this->canUseMultipleSourceAccounts(),
				'hashtags'         => $this->canUseHashtagSources(),
				'taggedPosts'      => $this->canUseTaggedPosts(),
			],
			'filters'     => [
				'typesOfPosts'      => $this->canUseTypesOfPostsFiltering(),
				'engagementSorting' => $this->canUseEngagementSorting(),
				'caption'           => $this->canUseCaptionFiltering(),
				'hashtag'           => $this->canUseHashtagFiltering(),
				'moderation'        => $this->canUseModerationFiltering(),
				'customLinks'       => $this->canUseCustomLinks(),
			],
			'design'      => [
				'slideOutPanel'         => $this->canUseSlideOutPanel(),
				'globalFeedVisibility'  => $this->canUseGlobalFeedVisibility(),
				'mobileModeSmartSnap'   => $this->canUseMobileFriendlyMode( 'smart_snap' ),
				'mobileModeClassicScroll' => $this->canUseMobileFriendlyMode( 'classic_scroll' ),
				'layoutGridCards'       => $this->canUseLayoutVariant( 'grid', 'grid_cards' ),
				'layoutHighlightDiscovery' => $this->canUseLayoutVariant( 'highlight', 'highlight_discovery' ),
				'layoutHighlightSuper'  => $this->canUseLayoutVariant( 'highlight', 'highlight_super' ),
				'layoutMasonryCards'    => $this->canUseLayoutVariant( 'masonry', 'masonry_cards' ),
				'layoutSliderCards'     => $this->canUseLayoutVariant( 'slider', 'slider_cards' ),
				'layoutSliderShowcase'  => $this->canUseLayoutVariant( 'slider', 'showcase' ),
				'layoutSliderInfinite'  => $this->canUseLayoutVariant( 'slider', 'infinite' ),
			],
			'interaction' => [
				'lightbox'   => $this->canUseImageClickAction( 'lightbox' ),
				'playInGrid' => $this->canUseImageClickAction( 'play_in_grid' ),
			],
		];
	}

	private function resolveProEnabled(): bool {
		return FreemiusAccess::canUsePremiumCode();
	}
}
