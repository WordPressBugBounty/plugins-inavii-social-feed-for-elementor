<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Assets;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Logger\Admin\DebugPage;

final class LegacyAdminAssetsLoader {
	private const HANDLE       = 'inavii-social-feed-app-script';
	private const STYLE_HANDLE = 'inavii-social-feed-app-style';
	private const BASE_PATH    = 'includes/Legacy/Assets/react';
	private const SCRIPT_FILE  = 'static/js/inavii-social-feed-app.min.js';
	private const STYLE_FILE   = 'static/css/inavii-social-feed-app.min.css';

	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'loadAdminAssets' ] );
	}

	public function loadAdminAssets( string $pageSlug ): void {
		if ( ! Plugin::isLegacyUi() ) {
			return;
		}

		if ( DebugPage::isEnabled() ) {
			return;
		}

		if ( ! $this->isSettingsPage( $pageSlug ) ) {
			return;
		}

		wp_enqueue_media();

		$scriptPath = $this->assetPath( self::BASE_PATH . '/' . self::SCRIPT_FILE );
		if ( ! is_file( $scriptPath ) ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			$this->assetUrl( self::BASE_PATH . '/' . self::SCRIPT_FILE ),
			[],
			Plugin::version(),
			true
		);

		$stylePath = $this->assetPath( self::BASE_PATH . '/' . self::STYLE_FILE );
		if ( is_file( $stylePath ) ) {
			wp_enqueue_style(
				self::STYLE_HANDLE,
				$this->assetUrl( self::BASE_PATH . '/' . self::STYLE_FILE ),
				[],
				Plugin::version()
			);
		}

		$this->localizeConfig();
	}

	private function localizeConfig(): void {
		$restRoot      = (string) get_rest_url();
		$adminUrl      = (string) admin_url();
		$assetsBaseUrl = $this->assetUrl( self::BASE_PATH );
		$debugUrl      = DebugPage::debugUrl();
		$nonce         = (string) wp_create_nonce( 'wp_rest' );

		$bootstrap = [
			'restBase'   => trailingslashit( (string) get_rest_url( null, 'inavii/v2' ) ),
			'nonce'      => $nonce,
			'adminUrl'   => $adminUrl,
			'assetsBase' => $assetsBaseUrl,
			'debugUrl'   => $debugUrl,
		];

		$legacyConfig = [
			'url'          => $restRoot,
			'nonce'        => $nonce,
			'redirect_url' => $adminUrl,
			'mediaUrl'     => $assetsBaseUrl,
			'debugUrl'     => $debugUrl,
		];

		wp_localize_script( self::HANDLE, 'inaviiSocialFeedConfig', $legacyConfig );
		wp_add_inline_script(
			self::HANDLE,
			'window.INAVII_BOOTSTRAP=' . wp_json_encode( $bootstrap ) . ';',
			'before'
		);
	}

	private function assetUrl( string $path ): string {
		$path = ltrim( $path, '/' );
		return rtrim( INAVII_INSTAGRAM_URL, '/' ) . '/' . $path;
	}

	private function assetPath( string $path ): string {
		$path = ltrim( $path, '/' );
		return rtrim( INAVII_INSTAGRAM_DIR, '/' ) . '/' . $path;
	}

	private function isSettingsPage( string $pageSlug ): bool {
		if ( $pageSlug === 'toplevel_page_inavii-instagram-settings' ) {
			return true;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		return $page === 'inavii-instagram-settings';
	}
}
