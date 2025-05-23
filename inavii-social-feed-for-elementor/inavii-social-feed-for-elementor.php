<?php

/**
 * Plugin Name: Inavii for Elementor Social Feed
 * Description: Add Instagram to your website in less than a minute with our dedicated plugin for Elementor. Just 4 simple steps will allow you to display your Instagram profile on your site, captivating visitors with beautiful photos and layouts.
 * Plugin URI:  https://www.inavii.com/
 * Version:     2.7.11
 * Author:      INAVII
 * Author URI:  https://www.inavii.com/
 * Elementor tested up to: 3.28.4
 * Requires PHP: 7.4
  */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('INAVII_SOCIAL_FEED_E_VERSION')) {
    define('INAVII_SOCIAL_FEED_E_VERSION', '2.7.11');

    define('INAVII_SOCIAL_FEED_E_MINIMUM_ELEMENTOR_VERSION', '3.10.0');
    define('INAVII_SOCIAL_FEED_E_MINIMUM_PHP_VERSION', '7.4');

    define('INAVII_SOCIAL_FEED_E_TEXT_DOMAIN', 'inavii-social-feed-e');

    define('INAVII_INSTAGRAM_URL', trailingslashit(plugin_dir_url(__FILE__)));
    define('INAVII_INSTAGRAM_DIR', trailingslashit(plugin_dir_path(__FILE__)));
    define('INAVII_INSTAGRAM_DIR_TWIG_VIEWS', trailingslashit(plugin_dir_path(__FILE__) . 'includes/Integration/Widgets/view'));
    define('INAVII_INSTAGRAM_DIR_TWIG_VIEWS_AJAX', trailingslashit(plugin_dir_path(__FILE__) . 'core/RestApi/EndPoints/Front/'));
    define('INAVII_TEMPLATE', trailingslashit(plugin_dir_path(__FILE__) . 'includes/Integration/PredefinedSections/templates'));
}


if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once INAVII_INSTAGRAM_DIR . '/vendor/autoload.php';
}

if (!function_exists('inavii_social_feed_e_fs_uninstall_cleanup')) {
    require_once __DIR__ . '/cleanup.php';
}

if (!function_exists('inavii_social_feed_e_fs')) {
    require_once __DIR__ . '/freemius.php';
}

if (!function_exists('inavii_social_feed_init')) {
    function inavii_social_feed_init()
    {
        require_once INAVII_INSTAGRAM_DIR . '/app.php';

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'inavii_social_feed_add_action_link');
    }
}

if (!function_exists('inavii_social_feed_save_version_history')) {
	function inavii_social_feed_save_version_history()
	{
		$current_version = INAVII_SOCIAL_FEED_E_VERSION;

		$version_history = get_option('inavii_social_feed_version_history', []);

		if (!is_array($version_history)) {
			$version_history = [];
		}

		if (!in_array($current_version, $version_history, true)) {
			$version_history[] = $current_version;
			update_option('inavii_social_feed_version_history', $version_history, false);
		}
	}
}

if (!function_exists('inavii_social_feed_add_action_link')) {
    function inavii_social_feed_add_action_link($links)
    {
        $settings_link = '<a href="' . esc_url(get_admin_url(null, 'admin.php?page=inavii-instagram-settings')) . '">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    }
}

if (!function_exists('inavii_social_feed_register_actions')) {
    function inavii_social_feed_register_actions()
    {
        if (!wp_next_scheduled('inavii_social_feed_update_media')) {
            wp_schedule_event(time(), 'hourly', 'inavii_social_feed_update_media');
        }

        if (!wp_next_scheduled('inavii_social_feed_refresh_token')) {
            wp_schedule_event(time(), 'weekly', 'inavii_social_feed_refresh_token');
        }

        if (get_option('inavii_social_feed_e_version', false) === false) {
            update_option('inavii_social_feed_first_active', true);
            update_option('inavii_social_feed_render_type', 'PHP');
        }

        update_option('inavii_social_feed_e_version', INAVII_SOCIAL_FEED_E_VERSION);

	    inavii_social_feed_save_version_history();

        if (inavii_social_feed_redirect_on_activation()) {
            add_option('inavii_social_feed_plugin_do_activation_redirect', sanitize_text_field(__FILE__));
        }
    }
}

if (!function_exists('inavii_social_feed_redirect_on_activation')) {
    function inavii_social_feed_redirect_on_activation()
    {
        if (is_network_admin() || !current_user_can('manage_options') || (defined('WP_DEBUG') && WP_DEBUG)) {
            return false;
        }

        $maybe_multi = filter_input(INPUT_GET, 'activate-multi', FILTER_VALIDATE_BOOLEAN);

        return !$maybe_multi;
    }
}

if (!function_exists('inavii_social_feed_plugin_activate_redirect')) {
    function inavii_social_feed_plugin_activate_redirect()
    {
        if (!inavii_social_feed_redirect_on_activation() && !is_admin()) {
            return;
        }

        if (__FILE__ === get_option('inavii_social_feed_plugin_do_activation_redirect')) {
            delete_option('inavii_social_feed_plugin_do_activation_redirect');
            wp_safe_redirect(esc_url(admin_url('admin.php?page=inavii-instagram-settings')));
            exit;
        }
    }
}

if (!function_exists('inavii_social_feed_deactivate_actions')) {
    function inavii_social_feed_deactivate_actions()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        wp_clear_scheduled_hook('inavii_social_feed_update_media');
        wp_clear_scheduled_hook('inavii_social_feed_refresh_token');
	    delete_option('inavii_social_feed_cron_last_status');
    }
}

register_activation_hook(__FILE__, 'inavii_social_feed_register_actions');
register_deactivation_hook(__FILE__, 'inavii_social_feed_deactivate_actions');

add_action('plugins_loaded', 'inavii_social_feed_init');
add_action('admin_init', 'inavii_social_feed_plugin_activate_redirect');
