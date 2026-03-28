<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Assets;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Includes\Legacy\RestApi\RestApiPublicAuthToken;
use Inavii\Instagram\Freemius\FreemiusAccess;

final class LegacyFrontAssetsLoader {
	private const SCRIPT_HANDLE   = 'inavii-widget-handlers';
	private const STYLE_HANDLE    = 'inavii-styles';
	private const TEMPLATE_HANDLE = 'inavii-social-feed-template-library';
	private const TEMPLATE_STYLE  = 'inavii-predefined-template-style-style';
	private const ASSETS_BASE     = 'includes/Legacy/Assets';
	private const TEMPLATE_PATH   = self::ASSETS_BASE . '/templates/dist/bundle.js';
	private const TEMPLATE_CSS    = self::ASSETS_BASE . '/templates/dist/main.css';
	private const LEGACY_WIDGET_TYPE = 'inavii-grid';

	private $legacyWidgetDetected = null;

	public function init(): void {
		add_action( 'elementor/frontend/before_enqueue_styles', [ $this, 'enqueueElementorStyles' ] );
		add_action( 'elementor/frontend/before_enqueue_scripts', [ $this, 'enqueueElementorScripts' ] );

		// Legacy Elementor editor panel assets are only needed in legacy UI.
		// In V3 they can conflict with Elementor panel internals on mixed (legacy + v3) pages.
		if ( Plugin::isLegacyUi() ) {
			add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueueEditorScripts' ] );
			add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueueEditorStyles' ] );
		}

		add_action( 'inavii/social-feed/front/enqueue_mode_aware_assets', [ $this, 'enqueueIfNeeded' ], 20 );
	}

	public function enqueueIfNeeded(): void {
		if ( ! $this->shouldLoad() ) {
			return;
		}

		$this->enqueueElementorStyles();
		$this->enqueueElementorScripts();
	}

	public function enqueueElementorScripts(): void {
		if ( ! $this->shouldLoad() ) {
			return;
		}

		// Legacy bundle ships lodash and can override global "_" used by Elementor editor.
		// In V3 editor keep legacy widget static preview (HTML/CSS) and skip this JS bundle.
		if ( $this->shouldSkipLegacyJsInV3Editor() ) {
			return;
		}

		if ( wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		$script = $this->scriptPath();
		$scriptPath = INAVII_INSTAGRAM_DIR . $script;
		if ( ! is_file( $scriptPath ) ) {
			return;
		}

		$deps = [ 'jquery' ];
		if ( wp_script_is( 'elementor-frontend', 'registered' ) || wp_script_is( 'elementor-frontend', 'enqueued' ) ) {
			$deps[] = 'elementor-frontend';
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			INAVII_INSTAGRAM_URL . $script,
			$deps,
			Plugin::version(),
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'InaviiRestApi',
			[
				'baseUrl'   => get_rest_url() . 'inavii/v1/',
				'authToken' => RestApiPublicAuthToken::get(),
			]
		);
	}

	public function enqueueElementorStyles(): void {
		if ( ! $this->shouldLoad() ) {
			return;
		}

		if ( wp_style_is( self::STYLE_HANDLE, 'enqueued' ) ) {
			return;
		}

		$this->registerSwiperAssetsIfNeeded();

		$stylePath = INAVII_INSTAGRAM_DIR . $this->stylePath();
		if ( ! is_file( $stylePath ) ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			INAVII_INSTAGRAM_URL . $this->stylePath(),
			[ 'swiper' ],
			Plugin::version()
		);
	}

	public function enqueueEditorScripts(): void {
		if ( ! $this->shouldLoad() ) {
			return;
		}

		$editorHelper = self::ASSETS_BASE . '/dist/js/add-body-class-editor.js';
		$helperPath = INAVII_INSTAGRAM_DIR . $editorHelper;
		if ( is_file( $helperPath ) ) {
			wp_enqueue_script(
				'inavii-add-body-class-editor',
				INAVII_INSTAGRAM_URL . $editorHelper,
				[],
				Plugin::version(),
				true
			);
		}

		$templatePath = INAVII_INSTAGRAM_DIR . self::TEMPLATE_PATH;
		if ( ! is_file( $templatePath ) ) {
			return;
		}

		wp_enqueue_script(
			self::TEMPLATE_HANDLE,
			INAVII_INSTAGRAM_URL . self::TEMPLATE_PATH,
			[ 'wp-element' ],
			Plugin::version(),
			true
		);

		wp_localize_script(
			self::TEMPLATE_HANDLE,
			'InaviiPredefinedTemplates',
			[
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'url'          => get_rest_url(),
				'mediaUrl'     => INAVII_INSTAGRAM_URL . self::ASSETS_BASE . '/',
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'redirect_url' => admin_url(),
			]
		);
	}

	public function enqueueEditorStyles(): void {
		if ( ! $this->shouldLoad() ) {
			return;
		}

		$this->enqueueElementorStyles();

		$templateCssPath = INAVII_INSTAGRAM_DIR . self::TEMPLATE_CSS;
		if ( ! is_file( $templateCssPath ) ) {
			return;
		}

		wp_enqueue_style(
			self::TEMPLATE_STYLE,
			INAVII_INSTAGRAM_URL . self::TEMPLATE_CSS,
			[],
			Plugin::version()
		);
	}

	private function registerSwiperAssetsIfNeeded(): void {
		if ( ! wp_style_is( 'swiper', 'registered' ) && ! wp_style_is( 'swiper', 'enqueued' ) ) {
			wp_register_style(
				'swiper',
				INAVII_INSTAGRAM_URL . self::ASSETS_BASE . '/vendors/swiper-bundle.min.css',
				[],
				Plugin::version()
			);
		}
	}

	private function stylePath(): string {
		if ( FreemiusAccess::canUsePremiumCode() ) {
			return self::ASSETS_BASE . '/dist/css/inavii-styles-pro.min.css';
		}

		return self::ASSETS_BASE . '/dist/css/inavii-styles.min.css';
	}

	private function scriptPath(): string {
		if ( FreemiusAccess::canUsePremiumCode() ) {
			return self::ASSETS_BASE . '/dist/js/inavii-js-pro.min.js';
		}

		return self::ASSETS_BASE . '/dist/js/inavii-js.min.js';
	}

	private function shouldLoad(): bool {
		if ( Plugin::isLegacyUi() ) {
			return true;
		}

		return $this->hasLegacyWidgetOnCurrentRequest();
	}

	private function hasLegacyWidgetOnCurrentRequest(): bool {
		if ( $this->legacyWidgetDetected !== null ) {
			return (bool) $this->legacyWidgetDetected;
		}

		$postIds = $this->resolveCandidatePostIds();
		$detected = false;

		foreach ( $postIds as $postId ) {
			if ( $this->postContainsLegacyWidget( $postId ) ) {
				$detected = true;
				break;
			}
		}

		$this->legacyWidgetDetected = (bool) apply_filters(
			'inavii/social-feed/legacy/assets/should_load',
			$detected,
			$postIds
		);

		return (bool) $this->legacyWidgetDetected;
	}

	private function resolveCandidatePostIds(): array {
		$postIds = [];

		$queriedId = get_queried_object_id();
		if ( is_numeric( $queriedId ) && (int) $queriedId > 0 ) {
			$postIds[] = (int) $queriedId;
		}

		global $post;
		if ( $post instanceof \WP_Post && (int) $post->ID > 0 ) {
			$postIds[] = (int) $post->ID;
		}

		foreach ( [ 'post', 'post_id', 'preview_id', 'elementor-preview' ] as $key ) {
			$value = isset( $_REQUEST[ $key ] ) ? wp_unslash( $_REQUEST[ $key ] ) : '';
			$id = is_numeric( $value ) ? (int) $value : 0;
			if ( $id > 0 ) {
				$postIds[] = $id;
			}
		}

		$postIds = array_values( array_unique( array_filter( $postIds ) ) );
		return array_map( 'intval', $postIds );
	}

	private function postContainsLegacyWidget( int $postId ): bool {
		$elementorData = get_post_meta( $postId, '_elementor_data', true );
		if ( ! is_string( $elementorData ) || $elementorData === '' ) {
			return false;
		}

		$widgetTypes = $this->legacyWidgetTypes();
		foreach ( $widgetTypes as $widgetType ) {
			$needle = '"widgetType":"' . $widgetType . '"';
			if ( strpos( $elementorData, $needle ) !== false ) {
				return true;
			}
		}

		$decoded = json_decode( $elementorData, true );
		if ( ! is_array( $decoded ) ) {
			return false;
		}

		return $this->containsLegacyWidgetType( $decoded, $widgetTypes );
	}

	private function containsLegacyWidgetType( array $node, array $widgetTypes ): bool {
		$widgetType = isset( $node['widgetType'] ) ? (string) $node['widgetType'] : '';
		$elType = isset( $node['elType'] ) ? (string) $node['elType'] : '';

		if ( $elType === 'widget' && in_array( $widgetType, $widgetTypes, true ) ) {
			return true;
		}

		foreach ( $node as $value ) {
			if ( is_array( $value ) && $this->containsLegacyWidgetType( $value, $widgetTypes ) ) {
				return true;
			}
		}

		return false;
	}

	private function legacyWidgetTypes(): array {
		$types = apply_filters(
			'inavii/social-feed/legacy/assets/widget_types',
			[ self::LEGACY_WIDGET_TYPE ]
		);

		if ( ! is_array( $types ) ) {
			return [ self::LEGACY_WIDGET_TYPE ];
		}

		$normalized = [];
		foreach ( $types as $type ) {
			if ( is_string( $type ) && $type !== '' ) {
				$normalized[] = $type;
			}
		}

		return $normalized === [] ? [ self::LEGACY_WIDGET_TYPE ] : array_values( array_unique( $normalized ) );
	}

	private function shouldSkipLegacyJsInV3Editor(): bool {
		if ( Plugin::isLegacyUi() ) {
			return false;
		}

		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return false;
		}

		try {
			return \Elementor\Plugin::$instance->editor->is_edit_mode();
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}
