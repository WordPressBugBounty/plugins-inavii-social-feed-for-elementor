<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config;

final class Plugin {
	private const UI_V3 = 'v3';
	private const UI_V2 = 'v2';

	private static bool $initialized  = false;
	private static string $version    = '';
	private static string $name       = '';
	private static string $pluginUri  = '';
	private static string $requiresWp = '';
	private static string $slug       = '';
	private static string $prefix     = 'inavii_social_feed_';
	private static string $file       = '';

	/**
	 * Init plugin config values.
	 *
	 * @param string $file Main plugin file.
	 * @param array  $pluginData Plugin header data (from get_file_data).
	 */
	public static function init( string $file, array $pluginData ): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;
		self::$file        = $file;

		$version = $pluginData['version'] ?? '';
		if ( ! is_string( $version ) || $version === '' ) {
			$version = defined( 'INAVII_SOCIAL_FEED_E_VERSION' ) ? (string) INAVII_SOCIAL_FEED_E_VERSION : '';
		}

		$name       = $pluginData['name'] ?? '';
		$pluginUri  = $pluginData['plugin_uri'] ?? '';
		$requiresWp = $pluginData['requires_wp'] ?? '';

		self::$version    = is_string( $version ) ? $version : '';
		self::$name       = is_string( $name ) ? $name : '';
		self::$pluginUri  = is_string( $pluginUri ) ? $pluginUri : '';
		self::$requiresWp = is_string( $requiresWp ) ? $requiresWp : '';

		$basename   = basename( $file, '.php' );
		self::$slug = sanitize_title( (string) $basename );

		self::ensureUiVersionOption();
	}

	public static function version(): string {
		if ( self::$version === '' && defined( 'INAVII_SOCIAL_FEED_E_VERSION' ) ) {
			return (string) INAVII_SOCIAL_FEED_E_VERSION;
		}

		return self::$version;
	}

	public static function name(): string {
		return self::$name;
	}

	public static function pluginUri(): string {
		return self::$pluginUri;
	}

	public static function requiresWp(): string {
		return self::$requiresWp;
	}

	public static function slug(): string {
		return self::$slug;
	}

	public static function prefix(): string {
		return self::$prefix;
	}

	public static function activatedDate(): string {
		$date = get_option( 'inavii_social_feed_activated_at', '' );

		return is_string( $date ) ? $date : '';
	}

	/**
	 * @return array<int,string>
	 */
	public static function versionHistory(): array {
		$history = get_option( 'inavii_social_feed_version_history', [] );
		if ( ! is_array( $history ) ) {
			return [];
		}

		$filtered = [];
		foreach ( $history as $entry ) {
			if ( is_string( $entry ) && $entry !== '' ) {
				$filtered[] = $entry;
			}
		}

		return $filtered;
	}

	public static function wasInstalledBefore( string $version ): bool {
		$history = self::versionHistory();
		if ( $history === [] ) {
			return false;
		}

		foreach ( $history as $historyVersion ) {
			if ( version_compare( $historyVersion, $version, '<' ) ) {
				return true;
			}
		}

		return false;
	}

	public static function uiVersion(): string {
		$resolved = apply_filters( 'inavii/social-feed/ui_version', self::storedUiVersion() );
		if ( ! is_string( $resolved ) || $resolved === '' ) {
			return self::storedUiVersion();
		}

		return self::normalizeUiValue( $resolved );
	}

	public static function storedUiVersion(): string {
		$ui = get_option( 'inavii_social_feed_ui_version', '' );
		if ( is_string( $ui ) && $ui !== '' ) {
			return self::normalizeUiValue( $ui );
		}

		return self::detectDefaultUiVersion();
	}

	public static function isLegacyUi(): bool {
		return self::uiVersion() === self::UI_V2;
	}

	public static function setUiVersion( string $ui ): string {
		$normalized = self::normalizeUiValue( $ui );
		update_option( 'inavii_social_feed_ui_version', $normalized, false );

		return $normalized;
	}

	private static function ensureUiVersionOption(): void {
		$existing = get_option( 'inavii_social_feed_ui_version', '' );
		if ( is_string( $existing ) && $existing !== '' ) {
			return;
		}

		update_option( 'inavii_social_feed_ui_version', self::detectDefaultUiVersion(), false );
	}

	private static function detectDefaultUiVersion(): string {
		$currentVersion = (string) get_option( 'inavii_social_feed_e_version', '' );
		if ( $currentVersion !== '' && version_compare( $currentVersion, '3.0.0', '<' ) ) {
			return self::UI_V2;
		}

		if ( self::wasInstalledBefore( '3.0.0' ) ) {
			return self::UI_V2;
		}

		return self::UI_V3;
	}

	private static function normalizeUiValue( string $ui ): string {
		$ui = strtolower( trim( $ui ) );
		if ( $ui === 'legacy' ) {
			return self::UI_V2;
		}

		if ( $ui === self::UI_V2 ) {
			return self::UI_V2;
		}

		return self::UI_V3;
	}
}
