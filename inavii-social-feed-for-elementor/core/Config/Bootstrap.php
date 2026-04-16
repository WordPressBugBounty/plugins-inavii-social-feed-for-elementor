<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config;

use Inavii\Instagram\Account\Cron\AccountStatsCron;
use Inavii\Instagram\Account\Cron\AccountTokenCron;
use Inavii\Instagram\Admin\SettingsPage;
use Inavii\Instagram\Cron\Scheduler;
use Inavii\Instagram\Front\Shortcode;
use Inavii\Instagram\Includes\Integration\Cache\LiteSpeedOptimizeIntegration;
use Inavii\Instagram\Includes\Legacy\Bootstrap as LegacyBootstrap;
use Inavii\Instagram\Includes\Legacy\Assets\LegacyFrontAssetsLoader;
use Inavii\Instagram\Includes\Legacy\Integration\WidgetsManager as LegacyWidgetsManager;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyMigrationQueue;
use Inavii\Instagram\Logger\Admin\DebugPage;
use Inavii\Instagram\Logger\Logger;
use Inavii\Instagram\Media\Cron\MediaQueueCron;
use Inavii\Instagram\Media\Cron\MediaSourceCleanupCron;
use Inavii\Instagram\Media\Cron\MediaSyncCron;
use Inavii\Instagram\Feed\FeedPostType;
use Inavii\Instagram\Includes\Legacy\RestApi\RegisterRestApi;
use Inavii\Instagram\RestApi\RegisterRestApiV2;
use Inavii\Instagram\Wp\AppGlobalSettings;
use Inavii\Instagram\Wp\PostType;
use function Inavii\Instagram\Di\container;

final class Bootstrap {
	private string $pluginFile = '';

	public function init( string $pluginFile ): void {
		$this->pluginFile = $pluginFile;

		$pluginData = get_file_data(
			$pluginFile,
			[
				'version'     => 'Version',
				'name'        => 'Plugin Name',
				'plugin_uri'  => 'Plugin URI',
				'requires_wp' => 'Requires at least',
			],
			'plugin'
		);

		Env::init( $pluginFile );
		Plugin::init( $pluginFile, $pluginData );

		register_activation_hook( $pluginFile, [ $this, 'activate' ] );
		register_deactivation_hook( $pluginFile, [ $this, 'deactivate' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( $pluginFile ), [ $this, 'addActionLink' ] );
		add_action( 'admin_init', [ $this, 'maybeRedirect' ] );
		add_action( 'init', [ $this, 'maybeInstall' ], 4 );

		// Must run after theme functions are loaded, so UI version filter can force v2/v3 reliably.
		add_action( 'init', [ $this, 'boot' ], 1 );
		add_action( 'init', [ $this, 'registerPostTypes' ] );
		add_action( 'rest_api_init', [ $this, 'registerRestApi' ] );
		add_action( 'init', [ $this, 'registerShortcodes' ] );
		add_action( 'init', [ $this, 'registerAssets' ] );
		add_action( 'init', [ $this, 'bootstrap' ], 5 );
	}

	public function boot(): void {
		container()->get( AppGlobalSettings::class );
		container()->get( LegacyMigrationQueue::class )->register();
		new LiteSpeedOptimizeIntegration();

		if ( Plugin::isLegacyUi() ) {
			( new LegacyBootstrap() )->init();
		} else {
			( new LegacyFrontAssetsLoader() )->init();
			// Keep legacy Elementor widget class registered in V3 so mixed pages (legacy + new widget)
			// can still be opened and edited safely.
			new LegacyWidgetsManager();
		}

		if ( is_admin() ) {
			SettingsPage::instance();
			new DebugPage();

			$adminAssets = container()->get( AdminAssetsLoader::class );
			$adminAssets->init();
		}

		$elementorWidgetClass = 'Inavii\\Instagram\\Includes\\Integration\\Elementor\\ElementorWidget';
		if ( class_exists( $elementorWidgetClass ) ) {
			new $elementorWidgetClass();
		}

		$editorBlockClass = 'Inavii\\Instagram\\Includes\\Integration\\Editor\\InaviiFeedBlock';
		if ( class_exists( $editorBlockClass ) ) {
			( new $editorBlockClass() )->init();
		}
	}

	public function bootstrap(): void {
		try {
			/** @var Initializer $initializer */
			$initializer = container()->get( Initializer::class );
			$initializer->init();
		} catch ( \Throwable $e ) {
			Logger::error(
				'bootstrap',
				'Initialization failed.',
				[
					'error' => $e->getMessage(),
				]
			);
		}
	}

	public function activate( bool $networkWide = false ): void {
		/** @var Install $install */
		$install = container()->get( Install::class );
		$install->activate( $networkWide );

		if ( $this->shouldRedirectOnActivation() ) {
			add_option( 'inavii_social_feed_plugin_do_activation_redirect', sanitize_text_field( $this->pluginFile ) );
		}
	}

	public function deactivate(): void {
		/** @var Scheduler $scheduler */
		$scheduler = container()->get( Scheduler::class );
		$scheduler->unscheduleAll( MediaSyncCron::HOOK );
		$scheduler->unscheduleAll( MediaSourceCleanupCron::HOOK );
		$scheduler->unscheduleAll( MediaQueueCron::HOOK );
		$scheduler->unscheduleAll( AccountStatsCron::HOOK );
		$scheduler->unscheduleAll( AccountTokenCron::HOOK );

		// Legacy hooks cleanup.
		wp_clear_scheduled_hook( 'inavii_social_feed_update_media' );
		wp_clear_scheduled_hook( 'inavii_social_feed_refresh_token' );
		wp_clear_scheduled_hook( LegacyMigrationQueue::HOOK );
		delete_option( 'inavii_social_feed_cron_last_status' );
	}

	public function registerPostTypes(): void {
		// Shared by legacy and new UI flows.
		PostType::register( new FeedPostType() );
	}

	public function registerRestApi(): void {
		if ( Plugin::isLegacyUi() ) {
			RegisterRestApi::registerRoute();
		} else {
			// Keep legacy front widget endpoints available for upgraded sites running old Elementor widgets.
			RegisterRestApi::registerFrontRoutes();
		}
		RegisterRestApiV2::registerRoute();
	}

	public function registerShortcodes(): void {
		$shortcode = container()->get( Shortcode::class );
		$shortcode->init();
	}

	public function registerAssets(): void {
		$assets = container()->get( FrontAssetsLoader::class );
		$assets->init();
	}

	public function addActionLink( array $links ): array {
		$settings_link = '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=inavii-instagram-settings' ) ) . '">Settings</a>';
		$links[]       = $settings_link;
		return $links;
	}

	public function maybeRedirect(): void {
		if ( ! $this->shouldRedirectOnActivation() ) {
			return;
		}

		if ( $this->pluginFile === get_option( 'inavii_social_feed_plugin_do_activation_redirect' ) ) {
			delete_option( 'inavii_social_feed_plugin_do_activation_redirect' );
			wp_safe_redirect( esc_url( admin_url( 'admin.php?page=inavii-instagram-settings' ) ) );
			exit;
		}
	}

	public function maybeInstall(): void {
		if ( ! $this->shouldRunInstall() ) {
			return;
		}

		/** @var Install $install */
		$install = container()->get( Install::class );
		$install->maybe_install();
	}

	private function shouldRunInstall(): bool {
		if ( is_admin() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return true;
		}

		$allowFront = (bool) apply_filters( 'inavii/social-feed/install/allow_front', false );
		if ( $allowFront ) {
			return true;
		}

		$cacheKey = 'inavii_social_feed_install_check';
		if ( get_transient( $cacheKey ) ) {
			return false;
		}

		set_transient( $cacheKey, '1', DAY_IN_SECONDS );
		return true;
	}

	private function shouldRedirectOnActivation(): bool {
		if ( is_network_admin() || ! current_user_can( 'manage_options' ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return false;
		}

		$maybeMulti = filter_input( INPUT_GET, 'activate-multi', FILTER_VALIDATE_BOOLEAN );
		return ! $maybeMulti;
	}
}
