<?php

namespace Inavii\Instagram\Includes\Dependence;

use Inavii\Instagram\RestApi\RestApiPublicAuthToken;
use Inavii\Instagram\Utils\VersionChecker;
class RegisterAssets {
    public function __construct() {
        add_action( 'elementor/frontend/before_enqueue_styles', array($this, 'inaviiStyles') );
        add_action( 'elementor/frontend/before_enqueue_scripts', array($this, 'enqueuePluginAssets') );
        add_action( 'elementor/editor/after_enqueue_scripts', array($this, 'addBodyClassEditor') );
        add_action( 'elementor/editor/after_enqueue_styles', array($this, 'registerStyleCssEditor') );
        add_action( 'admin_enqueue_scripts', array($this, 'loadReactAppInDashboard') );
    }

    public function enqueuePluginAssets() : void {
        wp_enqueue_script(
            'inavii-widget-handlers',
            INAVII_INSTAGRAM_URL . $this->getScriptPath(),
            array('jquery', 'elementor-frontend'),
            INAVII_SOCIAL_FEED_E_VERSION,
            true
        );
        wp_localize_script( 'inavii-widget-handlers', 'InaviiRestApi', array(
            'baseUrl'   => get_rest_url() . 'inavii/v1/',
            'authToken' => RestApiPublicAuthToken::get(),
        ) );
    }

    public function addBodyClassEditor() : void {
        wp_enqueue_script(
            'inavii-add-body-class-editor',
            INAVII_INSTAGRAM_URL . 'assets/dist/js/add-body-class-editor.js',
            array(),
            INAVII_SOCIAL_FEED_E_VERSION
        );
        wp_enqueue_script(
            'inavii-social-feed-template-library',
            INAVII_INSTAGRAM_URL . 'assets/templates/dist/bundle.js',
            array('wp-element'),
            INAVII_SOCIAL_FEED_E_VERSION
        );
        wp_localize_script( 'inavii-social-feed-template-library', 'InaviiPredefinedTemplates', [
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'url'          => get_rest_url(),
            'mediaUrl'     => INAVII_INSTAGRAM_URL . 'assets/',
            'nonce'        => wp_create_nonce( 'wp_rest' ),
            'redirect_url' => admin_url(),
        ] );
    }

    public function inaviiStyles() : void {
        wp_enqueue_style(
            'inavii-styles',
            INAVII_INSTAGRAM_URL . $this->getStylePath(),
            array(),
            INAVII_SOCIAL_FEED_E_VERSION
        );
        if ( wp_script_is( 'swiper', 'registered' ) === false ) {
            wp_enqueue_style( 'slider-swiper-css', INAVII_INSTAGRAM_URL . 'assets/vendors/swiper-bundle.min.css' );
        }
    }

    public function registerStyleCssEditor() {
        wp_enqueue_style(
            'inavii-styles',
            INAVII_INSTAGRAM_URL . $this->getStylePath(),
            array(),
            INAVII_SOCIAL_FEED_E_VERSION
        );
        wp_enqueue_style(
            'inavii-predefined-template-style-style',
            INAVII_INSTAGRAM_URL . 'assets/templates/dist/main.css',
            array(),
            INAVII_SOCIAL_FEED_E_VERSION
        );
    }

    public function loadReactAppInDashboard( $pageSlug ) : void {
        if ( $pageSlug === 'toplevel_page_inavii-instagram-settings' ) {
            wp_enqueue_style(
                'inavii-social-feed-app-style',
                INAVII_INSTAGRAM_URL . 'assets/react/static/css/inavii-social-feed-app.min.css',
                array(),
                INAVII_SOCIAL_FEED_E_VERSION
            );
            wp_enqueue_media();
            wp_enqueue_script(
                'inavii-social-feed-app-script',
                INAVII_INSTAGRAM_URL . 'assets/react/static/js/inavii-social-feed-app.min.js',
                array(),
                INAVII_SOCIAL_FEED_E_VERSION
            );
        }
        wp_localize_script( 'inavii-social-feed-app-script', 'inaviiSocialFeedConfig', array(
            'url'          => get_rest_url(),
            'nonce'        => wp_create_nonce( 'wp_rest' ),
            'redirect_url' => admin_url(),
            'mediaUrl'     => INAVII_INSTAGRAM_URL . 'assets/react',
        ) );
    }

    private function getStylePath() : string {
        return 'assets/dist/css/inavii-styles.min.css';
    }

    private function getScriptPath() : string {
        return 'assets/dist/js/inavii-js.min.js';
    }

}
