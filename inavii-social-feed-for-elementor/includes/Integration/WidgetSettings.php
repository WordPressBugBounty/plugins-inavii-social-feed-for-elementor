<?php

namespace Inavii\Instagram\Includes\Integration;

use Inavii\Instagram\Includes\Integration\Widgets\WidgetsBase;
use Inavii\Instagram\Media\Media;
use Inavii\Instagram\Utils\VersionChecker;
class WidgetSettings {
    private $settings;

    public function __construct( array $settings ) {
        $this->settings = $settings;
    }

    private function setting( $name ) {
        if ( isset( $this->settings[$name] ) && !empty( $this->settings[$name] ) ) {
            return $this->settings[$name];
        }
        return false;
    }

    protected function feeds_contains( $haystack, $needle ) : bool {
        return $haystack === $needle;
    }

    private function freeLayout() : array {
        return [
            WidgetsBase::GRID,
            WidgetsBase::GALLERY,
            WidgetsBase::ROW,
            WidgetsBase::WAVE
        ];
    }

    public function isAvailableLayout() : bool {
        if ( VersionChecker::version()->can_use_premium_code() ) {
            return true;
        }
        $selectedLayout = $this->setting( 'feeds_layout' );
        if ( strpos( $selectedLayout, ':' ) === false ) {
            if ( !in_array( $selectedLayout, $this->freeLayout() ) ) {
                return false;
            }
        }
        if ( $selectedLayout === null || strpos( $selectedLayout, ':' ) === false ) {
            return true;
        }
        $layout = explode( ':', $selectedLayout );
        $selectedLayoutValue = (string) $layout[1];
        if ( !in_array( $selectedLayoutValue, $this->freeLayout() ) ) {
            return false;
        }
        return true;
    }

    public function feedId() : int {
        /**
         * Support for versions less than 2.6.8
         */
        $feedLayouts = $this->getFeedLayouts();
        if ( $feedLayouts === 0 ) {
            return ( is_numeric( $this->setting( 'feeds_id' ) ) ? (int) $this->setting( 'feeds_id' ) : 0 );
        }
        return $feedLayouts;
    }

    /**
     * Support for versions less than 2.6.8
     */
    private function getFeedLayouts() : int {
        $feedLayouts = $this->setting( 'feeds_layout' );
        if ( !is_string( $feedLayouts ) ) {
            return 0;
        }
        $feedId = explode( ':', $feedLayouts );
        return ( isset( $feedId[0] ) && is_numeric( $feedId[0] ) ? (int) $feedId[0] : 0 );
    }

    public function enableFollowButton() : bool {
        return $this->setting( 'enable_follow_button' ) === 'yes';
    }

    public function followButtonText() {
        return $this->setting( 'follow_button_text' );
    }

    public function followButtonIcon() {
        return $this->setting( 'follow_icon_button' );
    }

    public function enableHeaderFollowButton() : bool {
        return $this->setting( 'enable_header_follow_button' ) === 'yes';
    }

    public function headerFollowButtonText() {
        return $this->setting( 'header_follow_button_text' );
    }

    public function headerFollowButtonIcon() {
        return $this->setting( 'header_follow_icon_button' );
    }

    public function getSliderHeight( $device = 'desktop' ) {
        switch ( $device ) {
            case 'tablet':
                $height = $this->setting( 'slider_height_tablet' )['size'] ?? null;
                break;
            case 'mobile':
                $height = $this->setting( 'slider_height_mobile' )['size'] ?? null;
                break;
            default:
                $height = $this->setting( 'slider_height' )['size'] ?? 220;
                break;
        }
        if ( $device === 'tablet' && !$height ) {
            $height = $this->getSliderHeight( 'desktop' );
        }
        if ( $device === 'mobile' && !$height ) {
            $height = $this->getSliderHeight( 'tablet' );
        }
        return $height;
    }

    public function userNameHeaderChoice() : string {
        return $this->setting( 'user_name_header_choice' ) ?? 'username';
    }

    public function userNameLightboxChoice() : string {
        return $this->setting( 'user_name_lightbox_choice' ) ?? 'username';
    }

    public function postsCountDefault() : int {
        return $this->setting( 'posts_counter' );
    }

    public function postsCountGridDefault() : int {
        return $this->setting( 'posts_counter_grid' );
    }

    public function postsCountGalleryDefault() : int {
        return $this->setting( 'posts_counter_gallery' );
    }

    public function postsCountDesktop5() : int {
        return $this->setting( 'posts_counter_highlight_super' );
    }

    public function postsCountMobile() : int {
        $feedsValue = $this->layoutType();
        if ( $this->feeds_contains( $feedsValue, WidgetsBase::ROW ) || $this->feeds_contains( $feedsValue, WidgetsBase::WAVE ) ) {
            return $this->postsCountMobile3();
        }
        return $this->setting( 'posts_counter_mobile' );
    }

    public function postsCountMobile9() : int {
        return $this->setting( 'posts_counter_mobile_9' );
    }

    public function postsCountMobile7() : int {
        return $this->setting( 'posts_counter_mobile_7' );
    }

    public function postsCountMobile5() : int {
        return $this->setting( 'posts_counter_mobile_5' );
    }

    public function postsCountMobile3() : int {
        return $this->setting( 'posts_counter_mobile_3' );
    }

    public function isMobile() : bool {
        return wp_is_mobile();
    }

    public function postsCount() : int {
        if ( wp_is_mobile() ) {
            return $this->postsCountMobile();
        }
        $feedsValue = $this->layoutType();
        if ( $this->feeds_contains( $feedsValue, WidgetsBase::GRID ) ) {
            return $this->postsCountGridDefault();
        }
        if ( $this->feeds_contains( $feedsValue, WidgetsBase::GALLERY ) ) {
            return $this->postsCountGalleryDefault();
        }
        if ( $this->feeds_contains( $feedsValue, WidgetsBase::WAVE ) ) {
            return $this->postsCountDesktop5();
        }
        if ( $this->feeds_contains( $feedsValue, WidgetsBase::ROW ) ) {
            return $this->postsCountDesktop5();
        }
        return $this->postsCountDefault();
    }

    public function layoutType() : string {
        $feedsValue = $this->setting( 'feeds_layout' );
        if ( !is_string( $feedsValue ) || empty( $feedsValue ) ) {
            return 'grid';
        }
        $layout = explode( ':', $feedsValue );
        if ( count( $layout ) === 1 ) {
            return $layout[0];
        }
        return $layout[1] ?? 'grid';
    }

    public function imgAnimation() : string {
        return ( $this->setting( 'hover_animation' ) ? 'elementor-animation-' . $this->setting( 'hover_animation' ) : '' );
    }

    public function target() : string {
        if ( $this->setting( 'post_link_target' ) === 'yes' ) {
            return '_blank';
        }
        return '_self';
    }

    public function enablePhotolinking() : string {
        return $this->setting( 'post_linking' );
    }

    public function imageSize() : string {
        if ( !Media::checkGDLibraryAvailability() ) {
            return 'full';
        }
        $size = $this->setting( 'image_size_feeds' );
        if ( $size === 'small' ) {
            return 'medium';
        }
        return $size;
    }

    public function layoutView() {
        return $this->setting( 'layout' );
    }

    public function enablePopupFollowButton() : bool {
        return $this->setting( 'enable_popup_follow_button' ) === 'yes';
    }

    public function popupFollowButtonText() {
        return $this->setting( 'popup_follow_button_text' );
    }

    public function enablePopupIconFollowButton() : bool {
        return $this->setting( 'enable_popup_icon_follow_button' ) === 'yes';
    }

    public function popupFollowButtonIcon() {
        return $this->setting( 'popup_follow_icon_button' );
    }

    public function enableLightboxFollowButton() : bool {
        return $this->setting( 'enable_lightbox_follow_button' ) === 'yes';
    }

    public function lightboxFollowButtonText() {
        return $this->setting( 'lightbox_follow_button_text' );
    }

    public function enableLightboxIconFollowButton() : bool {
        return $this->setting( 'enable_lightbox_icon_follow_button' ) === 'yes';
    }

    public function lightboxFollowButtonIcon() {
        return $this->setting( 'lightbox_follow_icon_button' );
    }

    public function enableAvatarLightbox() : bool {
        return $this->setting( 'avatar_lightbox_switch' ) === 'yes';
    }

    public function enableAvatarHeaderBox() : bool {
        return $this->setting( 'avatar_header_switch' ) === 'yes';
    }

    public function enableBioHeaderBox() : bool {
        return $this->setting( 'bio_header_switch' ) === 'yes';
    }

    public function enableUserNameLightbox() : bool {
        return $this->setting( 'user_name_lightbox_switch' ) === 'yes';
    }

    public function enableUserNameHeaderBox() : bool {
        return $this->setting( 'user_name_header_switch' ) === 'yes';
    }

    public function videoPlayback() : bool {
        if ( VersionChecker::version()->can_use_premium_code() ) {
            $playVideo = $this->setting( 'show_video_playback' );
            if ( $playVideo === 'no' || $playVideo === 'yes' ) {
                return true;
            }
            return false;
        }
        if ( (bool) get_option( 'inavii_social_feed_first_active', false ) === true ) {
            return false;
        }
        return true;
    }

    public function feedOffset() : int {
        return (int) $this->setting( 'feed_offset' ) ?? 0;
    }

}
