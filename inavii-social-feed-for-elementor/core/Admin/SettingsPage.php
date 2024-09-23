<?php

namespace Inavii\Instagram\Admin;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class SettingsPage {

	private static $instance;

	public function __construct() {

		if ( ! current_user_can( 'manage_options' ) || !is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_menu', array( $this, 'register_sub_menu' ) );
	}

	public function register_page(): void {
		add_menu_page(
			__( 'Inavii Social Feed', 'inavii-social-feed-e' ),
			'Inavii Social Feed',
			'manage_options',
			'inavii-instagram-settings',
			array( $this, 'render_page' ),
			'dashicons-instagram',30
		);
	}

    public function register_sub_menu(): void {
        add_submenu_page(
            'inavii-instagram-settings',
            'Feeds',
            'Feeds',
            'manage_options',
            'inavii-instagram-settings',
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'inavii-instagram-settings',
            'Accounts',
            'Accounts',
            'manage_options',
            'inavii-instagram-settings#/accounts',
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'inavii-instagram-settings',
            'Global Settings',
            'Global Settings',
            'manage_options',
            'inavii-instagram-settings#/global-settings',
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'inavii-instagram-settings',
            'Guides',
            'Guides',
            'manage_options',
            'inavii-instagram-settings#/guides',
            array( $this, 'render_page' )
        );
    }

	public function render_page(): void {
		?>
        <div id="inavii-social-feed-app"></div>
		<?php
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

