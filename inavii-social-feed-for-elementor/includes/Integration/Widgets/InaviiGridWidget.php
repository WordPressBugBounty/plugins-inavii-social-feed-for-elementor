<?php

namespace Inavii\Instagram\Includes\Integration\Widgets;

use Inavii\Instagram\FeedsManager\GetAccountsBySource;
use Inavii\Instagram\Includes\Integration\WidgetSettings;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Utils\TimeChecker;
use Inavii\Instagram\Utils\VersionChecker;
use Timber\Timber;
class InaviiGridWidget extends WidgetsBase {
    public function get_name() : string {
        return 'inavii-grid';
    }

    public function get_title() : string {
        return esc_html__( 'Inavii Social Feed', INAVII_SOCIAL_FEED_E_TEXT_DOMAIN );
    }

    public function get_icon() : string {
        return 'inavii-icon-instagram';
    }

    private function isAdmin() : bool {
        return is_user_logged_in() && current_user_can( 'manage_options' );
    }

    /**
     * Render text editor widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.r
     *
     * @since  1.0.0
     * @access protected
     */
    protected function render() : void {
        $this->settings = $this->get_settings_for_display();
        $widgetSettings = new WidgetSettings($this->settings);
        $feed = new FeedPostType();
        $feedId = $widgetSettings->feedId();
        if ( $feedId === 0 ) {
            Timber::render( 'view/no-posts.twig', [
                'message' => '<span>Please select </span> a feed',
            ] );
            return;
        }
        if ( $widgetSettings->isAvailableLayout() === false ) {
            Timber::render( 'view/no-posts.twig', [
                'message' => '<span>This layout is no longer available, please choose another one.</span>',
            ] );
            return;
        }
        $countAvailablePosts = $feed->countAvailablePosts( $feedId, $widgetSettings->postsCount() );
        $feedSettings = $feed->getSettings( $feedId );
        $account = ( new AccountPostType() )->getAccountRelatedWithFeed( $feedId );
        if ( $account->wpAccountID() === 0 ) {
            $account = ( new GetAccountsBySource($feedSettings) )->get();
        }
        $widgetData = [
            'feed_offset'                        => $widgetSettings->feedOffset(),
            'feed_id'                            => $feedId,
            'posts_count'                        => $widgetSettings->postsCount(),
            'posts_count_available'              => $countAvailablePosts,
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
            'avatar_url'                         => ( $account->avatarOverwritten() ?: $account->avatar() ),
            'username'                           => ( $widgetSettings->userNameHeaderChoice() === "name" ? $account->name() : $account->userName() ),
            'username_lightbox'                  => ( $widgetSettings->userNameLightboxChoice() === "name" ? $account->name() : $account->userName() ),
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
            'is_promotion'                       => $feedSettings['promotion'] ?? false,
            'is_pro'                             => VersionChecker::version()->can_use_premium_code() && VersionChecker::version()->is_premium(),
            'video_playback'                     => $widgetSettings->videoPlayback(),
        ];
        if ( $this->isAdmin() ) {
            try {
                $lastFeedUpdate = (int) TimeChecker::calculateTimeDifference( (string) $account->lastUpdate() )->days;
                if ( $account->issues()['reconnectRequired'] ) {
                    Timber::render( 'view/reconnect.twig', [
                        'lastUpdate' => $lastFeedUpdate,
                    ] );
                }
            } catch ( \Exception $e ) {
            }
        }
        Timber::render( 'view/index.twig', array_merge( [
            'widgetSettings' => $widgetData,
        ], array_merge( $widgetData, [
            'enable_follow_button'        => $widgetSettings->enableFollowButton(),
            'follow_button_icon'          => $this->icon( $widgetSettings->followButtonIcon() ),
            'follow_button_text'          => $widgetSettings->followButtonText(),
            'enable_header_follow_button' => $widgetSettings->enableHeaderFollowButton(),
            'header_follow_button_icon'   => $this->icon( $widgetSettings->headerFollowButtonIcon() ),
            'header_follow_button_text'   => $widgetSettings->headerFollowButtonText(),
            'enable_avatar_header_box'    => $widgetSettings->enableAvatarHeaderBox(),
            'username_header_box'         => $widgetSettings->enableUserNameHeaderBox(),
        ] ) ) );
    }

}
