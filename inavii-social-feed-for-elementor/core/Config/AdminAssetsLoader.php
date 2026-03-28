<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config;

use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;
use Inavii\Instagram\Front\Config;
use Inavii\Instagram\Logger\Admin\DebugPage;
use Inavii\Instagram\Settings\PricingPage;

final class AdminAssetsLoader {
	private const HANDLE         = 'inavii-social-feed-admin';
	private const ADMIN_BASEPATH = 'admin';
	private const SCRIPT_FILE    = 'admin.min.js';
	private const SCRIPT_DEPS    = [ 'react', 'react-dom', 'react-jsx-runtime' ];
	private Config $frontConfig;
	private ProFeaturesPolicy $proFeatures;

	public function __construct( Config $frontConfig, ProFeaturesPolicy $proFeatures ) {
		$this->frontConfig = $frontConfig;
		$this->proFeatures = $proFeatures;
	}

	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'loadAdminAssets' ] );
	}

	public function loadAdminAssets( string $pageSlug ): void {
		if ( Plugin::isLegacyUi() ) {
			return;
		}

		if ( DebugPage::isEnabled() ) {
			return;
		}

		if ( ! $this->isSettingsPage( $pageSlug ) ) {
			return;
		}

		$handle = $this->enqueueBundledAdmin();

		if ( $handle !== '' ) {
			$this->localizeAdminConfig( $handle );
		}
	}

	private function enqueueBundledAdmin(): string {
		$scriptPath = $this->assetPath( self::ADMIN_BASEPATH . '/' . self::SCRIPT_FILE );
		if ( ! is_file( $scriptPath ) ) {
			return '';
		}

		wp_enqueue_script(
			self::HANDLE,
			$this->assetUrl( self::ADMIN_BASEPATH . '/' . self::SCRIPT_FILE ),
			self::SCRIPT_DEPS,
			Plugin::version(),
			true
		);

		return self::HANDLE;
	}

	private function localizeAdminConfig( string $handle ): void {
		$adminUrl  = (string) admin_url( 'admin.php' );
		$assetsUrl = $this->assetUrl( self::ADMIN_BASEPATH );
		$debugUrl  = DebugPage::debugUrl();
		$nonce     = (string) wp_create_nonce( 'wp_rest' );

		$bootstrap = [
			'restBase'          => trailingslashit( (string) get_rest_url( null, 'inavii/v2' ) ),
			'nonce'             => $nonce,
			'adminUrl'          => $adminUrl,
			'assetsBase'        => $assetsUrl,
			'debugUrl'          => $debugUrl,
			'pricingUrl'        => PricingPage::url(),
			'capabilities'      => $this->proFeatures->capabilitiesForApi(),
			'isElementorActive' => defined( 'ELEMENTOR_VERSION' ),
			'config'            => $this->frontConfig->all(),
		];

		wp_add_inline_script(
			$handle,
			'window.INAVII_BOOTSTRAP=' . wp_json_encode( $bootstrap ) . ';',
			'before'
		);
	}

	private function assetUrl( string $path ): string {
		$path = ltrim( $path, '/' );
		return rtrim( Env::$assets_url, '/' ) . '/' . $path;
	}

	private function assetPath( string $path ): string {
		$path = ltrim( $path, '/' );
		return rtrim( Env::$assets_path, '/' ) . '/' . $path;
	}

	private function isSettingsPage( string $pageSlug ): bool {
		if ( $pageSlug === 'toplevel_page_inavii-instagram-settings' ) {
			return true;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		return $page === 'inavii-instagram-settings';
	}
}
