<?php

declare (strict_types = 1);
namespace Inavii\Instagram\Includes\Legacy\Integration\Widgets;

use Inavii\Instagram\Account\Application\Api\AccountApiService;
use Inavii\Instagram\Account\Domain\Account as DomainAccount;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Feed\Application\FeedService;
use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\Includes\Legacy\Integration\Views\Views;
use Inavii\Instagram\Includes\Legacy\Integration\WidgetSettings;
use Inavii\Instagram\Includes\Legacy\Front\LegacyFeedCacheReader;
use Inavii\Instagram\Includes\Legacy\Front\LegacyMediaViewMapper;
use Inavii\Instagram\Includes\Legacy\Front\LegacyPromotionHydrator;
use Inavii\Instagram\Includes\Legacy\PostTypes\Account\Account as LegacyAccount;
use Inavii\Instagram\Includes\Legacy\PostTypes\Account\AccountPostType as LegacyAccountPostType;
use Inavii\Instagram\Freemius\FreemiusAccess;
use Inavii\Instagram\Wp\AppGlobalSettings;
use function Inavii\Instagram\Di\container;
class InaviiGridWidget extends WidgetsBase {
    private FeedService $feedService;

    private AccountRepository $accounts;

    private AccountApiService $accountApi;

    private LegacyFeedCacheReader $legacyCache;

    private LegacyMediaViewMapper $legacyMapper;

    private LegacyPromotionHydrator $legacyPromotionHydrator;

    private LegacyAccountPostType $legacyAccounts;

    public function __construct( $data = [], $args = null ) {
        parent::__construct( $data, $args );
        $this->feedService = container()->get( FeedService::class );
        $this->accounts = container()->get( AccountRepository::class );
        $this->accountApi = container()->get( AccountApiService::class );
        $this->legacyCache = new LegacyFeedCacheReader();
        $this->legacyMapper = new LegacyMediaViewMapper();
        $this->legacyPromotionHydrator = new LegacyPromotionHydrator();
        $this->legacyAccounts = new LegacyAccountPostType();
    }

    public function get_name() : string {
        return 'inavii-grid';
    }

    public function get_title() : string {
        if ( !Plugin::isLegacyUi() ) {
            return esc_html__( 'Inavii Social Feed (Legacy)', 'inavii-social-feed' );
        }
        return esc_html__( 'Inavii Social Feed', 'inavii-social-feed' );
    }

    public function get_icon() : string {
        return 'inavii-icon-instagram';
    }

    public function show_in_panel() : bool {
        return Plugin::isLegacyUi();
    }

    private function getRenderOption() : string {
        $settings = new AppGlobalSettings();
        return $settings->getRenderOption();
    }

    private function getWidgetData(
        int $feedId,
        WidgetSettings $widgetSettings,
        array $feedSettings,
        LegacyAccount $account
    ) : array {
        return [
            'feed_offset'                        => $widgetSettings->feedOffset(),
            'feed_id'                            => $feedId,
            'posts_count'                        => $widgetSettings->postsCount(),
            'posts_count_available'              => $this->countAvailablePosts( $feedId ),
            'items'                              => [],
            'items_desktop'                      => $widgetSettings->postsCount(),
            'items_mobile'                       => $widgetSettings->postsCountMobile(),
            'is_mobile'                          => $widgetSettings->isMobile(),
            'img_animation_class'                => $widgetSettings->imgAnimation(),
            'follow_button_url'                  => $account->instagramProfileLink(),
            'enable_photo_linking'               => $widgetSettings->enablePhotolinking(),
            'target'                             => $widgetSettings->target(),
            'layoutView'                         => $widgetSettings->layoutView(),
            'imageSize'                          => $widgetSettings->imageSize(),
            'enable_avatar_lightbox'             => $widgetSettings->enableAvatarLightbox(),
            'avatar_url'                         => ( $account->avatarOverwritten() !== '' ? $account->avatarOverwritten() : $account->avatar() ),
            'username'                           => ( $widgetSettings->userNameHeaderChoice() === 'name' ? $account->name() : $account->userName() ),
            'username_lightbox'                  => ( $widgetSettings->userNameLightboxChoice() === 'name' ? $account->name() : $account->userName() ),
            'username_lightbox_switch'           => $widgetSettings->enableUserNameLightbox(),
            'widget_id'                          => $this->get_id(),
            'layout_type'                        => $widgetSettings->layoutType(),
            'enable_popup_follow_button'         => $widgetSettings->enablePopupFollowButton(),
            'enable_popup_icon_follow_button'    => $widgetSettings->enablePopupIconFollowButton(),
            'follow_popup_button_icon'           => $this->icon( $widgetSettings->popupFollowButtonIcon() ),
            'follow_popup_button_text'           => $widgetSettings->popupFollowButtonText(),
            'enable_lightbox_follow_button'      => $widgetSettings->enableLightboxFollowButton(),
            'enable_lightbox_icon_follow_button' => $widgetSettings->enableLightboxIconFollowButton(),
            'follow_lightbox_button_icon'        => $this->icon( $widgetSettings->lightboxFollowButtonIcon() ),
            'follow_lightbox_button_text'        => $widgetSettings->lightboxFollowButtonText(),
            'is_promotion'                       => $this->legacyPromotionHydrator->isPromotionEnabled( $feedSettings ),
            'is_pro'                             => FreemiusAccess::canUsePremiumCode(),
            'video_playback'                     => $widgetSettings->videoPlayback(),
            'render_type'                        => $this->getRenderOption(),
        ];
    }

    private function renderDynamicContent( int $feedId, WidgetSettings $widgetSettings, array $widgetData ) : string {
        if ( $this->getRenderOption() !== 'PHP' ) {
            return '';
        }
        $items = [];
        $feedSettings = $this->getFeedSettings( $feedId );
        try {
            $posts = $this->feedService->getMedia( $feedId, $widgetSettings->postsCount(), $widgetSettings->feedOffset() );
            $items = $this->legacyMapper->mapPosts( $this->legacyPromotionHydrator->apply( $posts->getPosts(), $feedSettings ) );
        } catch ( \Throwable $e ) {
            $items = [];
        }
        if ( $items === [] ) {
            $fallback = $this->legacyCache->getSlice( $feedId, $widgetSettings->postsCount(), $widgetSettings->feedOffset() );
            $items = $this->legacyMapper->mapPosts( $this->legacyPromotionHydrator->apply( $fallback['items'], $feedSettings ) );
        }
        return Views::renderWithAjax( array_merge( $widgetData, [
            'items' => $items,
        ] ) );
    }

    private function getFeedSettings( int $feedId ) : array {
        try {
            return $this->feedService->get( $feedId )->settings()->toArray();
        } catch ( \Throwable $e ) {
            $raw = get_post_meta( $feedId, FeedRepository::META_KEY_FEED_SETTINGS, true );
            return ( is_array( $raw ) ? $raw : [] );
        }
    }

    private function countAvailablePosts( int $feedId ) : int {
        try {
            $total = $this->feedService->getMedia( $feedId, 1, 0 )->getTotal();
            if ( $total > 0 ) {
                return $total;
            }
        } catch ( \Throwable $e ) {
            // Use legacy fallback when new feed storage is not available yet.
            return $this->legacyCache->count( $feedId );
        }
        return $this->legacyCache->count( $feedId );
    }

    private function resolvePrimaryAccountId( array $feedSettings ) : int {
        $source = ( isset( $feedSettings['source'] ) && is_array( $feedSettings['source'] ) ? $feedSettings['source'] : [] );
        $candidates = [];
        if ( isset( $source['primaryAccountId'] ) && is_numeric( $source['primaryAccountId'] ) ) {
            $candidates[] = (int) $source['primaryAccountId'];
        }
        foreach ( ['accounts', 'tagged'] as $key ) {
            $ids = $source[$key] ?? [];
            if ( !is_array( $ids ) ) {
                continue;
            }
            foreach ( $ids as $id ) {
                if ( is_numeric( $id ) ) {
                    $candidates[] = (int) $id;
                }
            }
        }
        $candidates = array_values( array_unique( array_filter( $candidates ) ) );
        return $candidates[0] ?? 0;
    }

    private function resolveAccount( array $feedSettings ) : LegacyAccount {
        $accountId = $this->resolvePrimaryAccountId( $feedSettings );
        if ( $accountId <= 0 ) {
            return new LegacyAccount([]);
        }
        try {
            $domainAccount = $this->accounts->get( $accountId );
        } catch ( \Throwable $e ) {
            return $this->resolveLegacyAccount( $accountId );
        }
        $reconnectRequired = false;
        $sourceError = '';
        try {
            $apiAccount = $this->accountApi->get( $accountId );
            $reconnectRequired = !empty( $apiAccount['reconnectRequired'] );
            $sourceError = ( isset( $apiAccount['sourceError'] ) ? (string) $apiAccount['sourceError'] : '' );
        } catch ( \Throwable $e ) {
            // Keep defaults when diagnostics are temporarily unavailable.
            $sourceError = '';
        }
        return $this->toLegacyAccount( $domainAccount, $reconnectRequired, $sourceError );
    }

    private function resolveLegacyAccount( int $accountId ) : LegacyAccount {
        $account = $this->legacyAccounts->get( $accountId );
        if ( trim( $account->userName() ) === '' ) {
            return new LegacyAccount([]);
        }
        return $account;
    }

    private function toLegacyAccount( DomainAccount $account, bool $reconnectRequired, string $sourceError ) : LegacyAccount {
        return new LegacyAccount([
            'wpAccountID'          => $account->id(),
            'id'                   => $account->igAccountId(),
            'accountType'          => $account->accountType(),
            'name'                 => $account->name(),
            'username'             => $account->username(),
            'accessToken'          => $account->accessToken(),
            'avatar'               => $account->avatar(),
            'avatarOverwritten'    => '',
            'mediaCount'           => $account->mediaCount(),
            'tokenExpires'         => $account->tokenExpires(),
            'biography'            => $account->biography(),
            'biographyOverwritten' => '',
            'lastUpdate'           => $account->lastUpdate() ?? '',
            'methodLastUpdate'     => '',
            'issues'               => [
                'count'             => ( $reconnectRequired ? 1 : 0 ),
                'error'             => $sourceError,
                'reconnectRequired' => $reconnectRequired,
            ],
        ]);
    }

    /**
     * Render the widget output on the frontend.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function render() : void {
        $this->settings = $this->get_settings_for_display();
        $widgetSettings = new WidgetSettings($this->settings);
        $feedId = $widgetSettings->feedId();
        if ( $feedId === 0 ) {
            Views::renderMessage( '<span>Please select </span> a feed' );
            return;
        }
        if ( !$widgetSettings->isAvailableLayout() ) {
            Views::renderMessage( '<span>This layout is no longer available, please choose another one.</span>' );
            return;
        }
        $feedSettings = $this->getFeedSettings( $feedId );
        $account = $this->resolveAccount( $feedSettings );
        $widgetData = $this->getWidgetData(
            $feedId,
            $widgetSettings,
            $feedSettings,
            $account
        );
        if ( FreemiusAccess::canUsePremiumCode() ) {
            $swiperOptions = $this->getSwiperOptions__premium_only( $widgetSettings );
            $widgetData = $this->mergePremiumData__premium_only(
                $widgetData,
                $widgetSettings,
                $swiperOptions,
                $account,
                $feedSettings
            );
            $widgetData['load_more_button_number_posts_to_load'] = $widgetSettings->loadMoreButtonNumberPostsToLoad__premium_only();
        }
        $data = array_merge( [
            'widgetSettings' => $widgetData,
        ], array_merge( $widgetData, [
            'enable_follow_button'        => $widgetSettings->enableFollowButton(),
            'enable_load_more_button'     => $widgetSettings->enableLoadMoreButton(),
            'load_more_button_text'       => $widgetSettings->loadMoreButtonText(),
            'follow_button_icon'          => $this->icon( $widgetSettings->followButtonIcon() ),
            'follow_button_text'          => $widgetSettings->followButtonText(),
            'enable_header_follow_button' => $widgetSettings->enableHeaderFollowButton(),
            'header_follow_button_icon'   => $this->icon( $widgetSettings->headerFollowButtonIcon() ),
            'header_follow_button_text'   => $widgetSettings->headerFollowButtonText(),
            'enable_avatar_header_box'    => $widgetSettings->enableAvatarHeaderBox(),
            'username_header_box'         => $widgetSettings->enableUserNameHeaderBox(),
            'dynamic_content'             => $this->renderDynamicContent( $feedId, $widgetSettings, $widgetData ),
        ] ) );
        Views::renderWithPhp( $data );
    }

}
