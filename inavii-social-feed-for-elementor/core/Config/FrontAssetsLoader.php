<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config;

use Inavii\Instagram\Freemius\FreemiusAccess;
use Inavii\Instagram\Front\Config;
use Inavii\Instagram\Front\PayloadRegistry;
use Inavii\Instagram\Includes\Integration\Elementor\ElementorEditorContext;
use Inavii\Instagram\Wp\AppGlobalSettings;

final class FrontAssetsLoader {
	private const HANDLE           = 'inavii-social-feed-front';
	private const LOADER_HANDLE    = 'inavii-social-feed-front-loader';
	private const FRONT_SCRIPT     = 'front/front.min.js';
	private const FRONT_PRO_SCRIPT = 'front/front-pro.min.js';
	private const FRONT_LOADER     = 'front/front-rest-loader.js';
	private const SCRIPT_DEPS      = [ 'react', 'react-dom', 'react-jsx-runtime' ];

	private PayloadRegistry $registry;
	private AppGlobalSettings $settings;
	private ElementorEditorContext $elementorContext;
	private Config $frontConfig;

	public function __construct(
		PayloadRegistry $registry,
		AppGlobalSettings $settings,
		ElementorEditorContext $elementorContext,
		Config $frontConfig
	) {
		$this->registry         = $registry;
		$this->settings         = $settings;
		$this->elementorContext = $elementorContext;
		$this->frontConfig      = $frontConfig;
	}

	public function init(): void {
		add_action( 'inavii/social-feed/front/enqueue_mode_aware_assets', [ $this, 'enqueue' ] );
		add_action( 'inavii/social-feed/front/enqueue_front_app_assets', [ $this, 'enqueueBundledAssets' ] );
		add_action( 'inavii/social-feed/front/enqueue_editor_preview_assets', [ $this, 'enqueueBundledEditorPreview' ] );
		$this->elementorContext->registerPreviewScriptHooks( [ $this, 'enqueueForElementorEditor' ] );
		add_action( 'wp_print_footer_scripts', [ $this, 'printPayloads' ], 5 );
	}

	public function enqueue(): void {
		if ( Plugin::isLegacyUi() ) {
			return;
		}

		if ( $this->isAjaxRenderMode() ) {
			$this->enqueueDynamicFrontLoader();
			return;
		}

		$this->enqueueBundledFront();
	}

	public function enqueueForElementorEditor(): void {
		if ( Plugin::isLegacyUi() ) {
			return;
		}

		if ( ! $this->elementorContext->isEditorContext() ) {
			return;
		}

		$this->enqueueBundledEditorPreview();
	}

	public function enqueueBundledEditorPreview(): void {
		if ( Plugin::isLegacyUi() ) {
			return;
		}

		$this->enqueueBundledFront();
	}

	public function enqueueBundledAssets(): void {
		if ( Plugin::isLegacyUi() ) {
			return;
		}

		$this->enqueueBundledFront();
	}

	private function enqueueBundledFront(): void {
		if ( wp_script_is( self::HANDLE, 'enqueued' ) ) {
			return;
		}

		$scriptUrl  = $this->selectedFrontScriptUrl();
		$scriptPath = $this->selectedFrontScriptPath();
		if ( ! is_file( $scriptPath ) ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			$scriptUrl,
			self::SCRIPT_DEPS,
			Plugin::version(),
			true
		);

		$config = $this->frontAppConfig();
		$inline = 'window.InaviiSocialFeedFrontConfig = ' . $this->safeJson( $config ) . ';';
		wp_add_inline_script( self::HANDLE, $inline, 'before' );
	}

	private function enqueueDynamicFrontLoader(): void {
		if ( wp_script_is( self::LOADER_HANDLE, 'enqueued' ) ) {
			return;
		}

		$appScriptPath = $this->selectedFrontScriptPath();
		$bootstrapPath = $this->assetPath( self::FRONT_LOADER );

		if ( ! is_file( $appScriptPath ) || ! is_file( $bootstrapPath ) ) {
			$this->enqueueBundledFront();
			return;
		}

		wp_enqueue_script(
			self::LOADER_HANDLE,
			$this->assetUrl( self::FRONT_LOADER ),
			self::SCRIPT_DEPS,
			Plugin::version(),
			true
		);

		$config                 = $this->frontAppConfig();
		$config['appScriptUrl'] = $this->selectedFrontScriptUrl();

		$inline = 'window.InaviiSocialFeedFrontConfig = ' . $this->safeJson( $config ) . ';';
		wp_add_inline_script( self::LOADER_HANDLE, $inline, 'before' );
	}

	private function frontAppConfig(): array {
		return [
			'feedsBaseUrl' => rest_url( 'inavii/v2/front/feeds/' ),
			'config'       => $this->frontConfig->all(),
		];
	}

	private function selectedFrontScriptUrl(): string {
		return $this->assetUrl( $this->selectedFrontScript() );
	}

	private function selectedFrontScriptPath(): string {
		return $this->assetPath( $this->selectedFrontScript() );
	}

	private function selectedFrontScript(): string {
		if ( FreemiusAccess::canUsePremiumCode() ) {
			$premiumPath = $this->assetPath( self::FRONT_PRO_SCRIPT );
			if ( is_file( $premiumPath ) ) {
				return self::FRONT_PRO_SCRIPT;
			}
		}

		return self::FRONT_SCRIPT;
	}

	public function printPayloads(): void {
		if ( $this->isAjaxRenderMode() ) {
			return;
		}

		if ( $this->registry->isEmpty() ) {
			return;
		}

		$json = $this->safeJson( $this->registry->all() );

		echo '<!-- INAVII SOCIAL FEED START -->';
		if ( function_exists( 'wp_print_inline_script_tag' ) ) {
			wp_print_inline_script_tag(
				$json,
				[
					'id'   => 'inavii-social-feed-payloads',
					'type' => 'application/json',
				]
			);
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe JSON string built in safeJson() for application/json script tag.
			echo '<script type="application/json" id="inavii-social-feed-payloads">' . $json . '</script>';
		}
		echo '<!-- INAVII SOCIAL FEED END -->';
	}

	/**
	 * @param array $data Payload data rendered in frontend.
	 */
	private function safeJson( array $data ): string {
		$json = wp_json_encode(
			$data,
			JSON_UNESCAPED_UNICODE
			| JSON_UNESCAPED_SLASHES
			| JSON_HEX_TAG
			| JSON_HEX_AMP
			| JSON_HEX_APOS
			| JSON_HEX_QUOT
		);

		return is_string( $json ) ? $json : '{}';
	}

	private function assetUrl( string $path ): string {
		$path = ltrim( $path, '/' );
		return rtrim( Env::$assets_url, '/' ) . '/' . $path;
	}

	private function assetPath( string $path ): string {
		$path = ltrim( $path, '/' );
		return rtrim( Env::$assets_path, '/' ) . '/' . $path;
	}

	private function isAjaxRenderMode(): bool {
		return strtoupper( $this->settings->getRenderOption() ) === 'AJAX';
	}
}
