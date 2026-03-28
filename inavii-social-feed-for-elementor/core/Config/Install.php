<?php

declare(strict_types=1);

namespace Inavii\Instagram\Config;

use Inavii\Instagram\Database\Database;

/**
 * Installer / migrator entrypoint.
 */
class Install {

	private Database $database;

	public function __construct( Database $database ) {
		$this->database = $database;
	}

	/**
	 * Fresh install (activation hook).
	 *
	 * @param bool $network_wide Whether the plugin is network-activated.
	 */
	public function activate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			$this->activate_network();
			return;
		}

		$this->ensure_schema();
		$this->ensure_version();
	}

	/**
	 * Runs on normal requests, so it also covers plugin updates.
	 */
	public function maybe_install(): void {
		if ( ! $this->shouldInstall() ) {
			return;
		}

		$this->ensure_schema();
		$this->ensure_version();
	}

	private function ensure_schema(): void {
		// Ensure wpdb helpers are available.
		$this->database->register_tables();

		// Create/upgrade tables when needed.
		$this->database->maybe_install();
	}

	private function activate_network(): void {
		// Per-site tables.
		$site_ids = get_sites( [ 'fields' => 'ids' ] );

		if ( ! is_array( $site_ids ) || $site_ids === [] ) {
			$this->ensure_schema();
			return;
		}

		foreach ( $site_ids as $site_id ) {
			$site_id = (int) $site_id;
			if ( $site_id <= 0 ) {
				continue;
			}

			switch_to_blog( $site_id );

			try {
				$this->ensure_schema();
			} finally {
				restore_current_blog();
			}
		}
	}

	private function ensure_version(): void {
		$current = (string) get_option( 'inavii_social_feed_e_version', '' );

		if ( $current === '' ) {
			update_option( 'inavii_social_feed_render_type', 'PHP' );
		}

		$this->ensure_ui_flag( $current );
		$this->cleanup_legacy_cron_hooks();

		if ( ! get_option( 'inavii_social_feed_activated_at', false ) ) {
			update_option( 'inavii_social_feed_activated_at', current_time( 'mysql' ) );
		}

		update_option( 'inavii_social_feed_e_version', Plugin::version() );

		$this->saveVersionHistory( Plugin::version() );
	}

	private function ensure_ui_flag( string $currentVersion ): void {
		$option = get_option( 'inavii_social_feed_ui_version', '' );
		if ( is_string( $option ) && $option !== '' ) {
			return;
		}

		$useLegacy = ( $currentVersion !== '' && version_compare( $currentVersion, '3.0.0', '<' ) )
			|| Plugin::wasInstalledBefore( '3.0.0' );

		update_option( 'inavii_social_feed_ui_version', $useLegacy ? 'v2' : 'v3' );
	}

	private function saveVersionHistory( string $version ): void {
		if ( $version === '' ) {
			return;
		}

		$history = get_option( 'inavii_social_feed_version_history', [] );
		if ( ! is_array( $history ) ) {
			$history = [];
		}

		if ( in_array( $version, $history, true ) ) {
			return;
		}

		$history[] = $version;
		update_option( 'inavii_social_feed_version_history', $history, false );
	}

	private function cleanup_legacy_cron_hooks(): void {
		wp_clear_scheduled_hook( 'inavii_social_feed_update_media' );
		wp_clear_scheduled_hook( 'inavii_social_feed_refresh_token' );
	}

	private function shouldInstall(): bool {
		$current = (string) get_option( 'inavii_social_feed_e_version', '' );
		if ( $current === '' ) {
			return true;
		}

		if ( $current !== Plugin::version() ) {
			return true;
		}

		if ( is_admin() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $this->database->needsInstall();
		}

		return false;
	}
}
