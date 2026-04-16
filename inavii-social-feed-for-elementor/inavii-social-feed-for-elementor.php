<?php

/**
 * Plugin Name: Inavii Social Feed
 * Description: Create beautiful Instagram feeds for your website in minutes with the Block Editor, shortcode, or Elementor widget.
 * Plugin URI:  https://www.inavii.com/
 * Version:     3.0.1
 * Author:      INAVII
 * Author URI:  https://www.inavii.com/
 * Elementor tested up to: 4.0.2
 * Requires PHP: 7.4
 *
  */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'INAVII_SOCIAL_FEED_E_VERSION' ) ) {
	define( 'INAVII_SOCIAL_FEED_E_VERSION', '3.0.1' );

	define( 'INAVII_SOCIAL_FEED_E_MINIMUM_ELEMENTOR_VERSION', '3.10.0' );
	define( 'INAVII_SOCIAL_FEED_E_MINIMUM_PHP_VERSION', '7.4' );

	define( 'INAVII_INSTAGRAM_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	define( 'INAVII_INSTAGRAM_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'INAVII_INSTAGRAM_DIR_TWIG_VIEWS', trailingslashit( plugin_dir_path( __FILE__ ) . 'includes/Integration/Widgets/view' ) );
	define( 'INAVII_INSTAGRAM_DIR_TWIG_VIEWS_AJAX', trailingslashit( plugin_dir_path( __FILE__ ) . 'core/RestApi/EndPoints/Front/' ) );
	define( 'INAVII_TEMPLATE', trailingslashit( plugin_dir_path( __FILE__ ) . 'includes/Integration/PredefinedSections/templates' ) );
}

if ( file_exists( INAVII_INSTAGRAM_DIR . '/vendor/autoload.php' ) ) {
	require_once INAVII_INSTAGRAM_DIR . '/vendor/autoload.php';
}

if ( file_exists( INAVII_INSTAGRAM_DIR . '/core/Di/ContainerConfigurator.php' ) ) {
	require_once INAVII_INSTAGRAM_DIR . '/core/Di/ContainerConfigurator.php';
}

if ( ! function_exists( 'inavii_social_feed_e_fs_uninstall_cleanup' ) ) {
	require_once INAVII_INSTAGRAM_DIR . '/cleanup.php';
}

if ( ! function_exists( 'inavii_social_feed_e_fs' ) ) {
	require_once INAVII_INSTAGRAM_DIR . '/freemius.php';
}

$inavii_app = \Inavii\Instagram\Di\container()->get( \Inavii\Instagram\Config\Bootstrap::class );
$inavii_app->init( __FILE__ );
