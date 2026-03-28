<?php

/**
 * Plugin Name: Inavii for Elementor Social Feed
 * Description: Add Instagram to your website in less than a minute with our dedicated plugin for Elementor. Just 4 simple steps will allow you to display your Instagram profile on your site, captivating visitors with beautiful photos and layouts.
 * Plugin URI:  https://www.inavii.com/
 * Version:     3.0.0
 * Author:      INAVII
 * Author URI:  https://www.inavii.com/
 * Elementor tested up to: 3.28.4
 * Requires PHP: 7.4
 *
  */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'INAVII_SOCIAL_FEED_E_VERSION' ) ) {
	define( 'INAVII_SOCIAL_FEED_E_VERSION', '3.0.0' );

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
