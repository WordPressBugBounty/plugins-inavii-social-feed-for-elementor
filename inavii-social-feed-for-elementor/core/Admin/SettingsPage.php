<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Admin;

use Inavii\Instagram\Config\Env;
use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Logger\Admin\DebugPage;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class SettingsPage {
	private const LOADER_FILE = 'images/inavii-loading.gif';

	private static $instance;

	public function __construct() {

		if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_menu', [ $this, 'register_sub_menu' ] );
		add_filter( 'parent_file', [ $this, 'highlight_parent_menu' ] );
		add_filter( 'submenu_file', [ $this, 'highlight_sub_menu' ] );
		add_action( 'admin_head', [ $this, 'print_admin_app_shell_styles' ], 1 );
		add_action( 'admin_head', [ $this, 'hide_debug_submenu' ] );
	}

	public function register_page(): void {
		add_menu_page(
			__( 'Inavii Social Feed', 'inavii-social-feed' ),
			'Inavii Social Feed',
			'manage_options',
			'inavii-instagram-settings',
			[ $this, 'render_page' ],
			'dashicons-instagram',
			30
		);
	}

	public function register_sub_menu(): void {
		if ( Plugin::isLegacyUi() ) {
			add_submenu_page(
				'inavii-instagram-settings',
				'Feeds',
				'Feeds',
				'manage_options',
				'inavii-instagram-settings',
				[ $this, 'render_page' ]
			);

			add_submenu_page(
				'inavii-instagram-settings',
				'Accounts',
				'Accounts',
				'manage_options',
				'inavii-instagram-settings#/accounts',
				[ $this, 'render_page' ]
			);

			add_submenu_page(
				'inavii-instagram-settings',
				'Global Settings',
				'Global Settings',
				'manage_options',
				'inavii-instagram-settings#/global-settings',
				[ $this, 'render_page' ]
			);

			add_submenu_page(
				'inavii-instagram-settings',
				'Guides',
				'Guides',
				'manage_options',
				'inavii-instagram-settings#/guides',
				[ $this, 'render_page' ]
			);
		} else {
			add_submenu_page(
				'inavii-instagram-settings',
				'Settings',
				'Settings',
				'manage_options',
				'inavii-instagram-settings&screen=settings',
				[ $this, 'render_page' ]
			);
		}

		add_submenu_page(
			'inavii-instagram-settings',
			'Debug Logs',
			'Debug Logs',
			'manage_options',
			'inavii-instagram-settings-debug',
			[ $this, 'render_page' ]
		);

		if ( ! Plugin::isLegacyUi() ) {
			remove_submenu_page( 'inavii-instagram-settings', 'inavii-instagram-settings' );
		}
	}

	public function render_page(): void {
		if ( DebugPage::isEnabled() ) {
			DebugPage::renderView();

			return;
		}

		$appClasses = 'inavii-social-feed';
		if ( Plugin::isLegacyUi() ) {
			$appClasses .= ' inavii-social-feed-legacy';
		}

		?>
		<div id="inavii-social-feed-app" class="<?php echo esc_attr( $appClasses ); ?>"></div>
		<?php if ( $this->shouldRenderV3Loader() ) : ?>
			<?php $loaderUrl = $this->loaderUrl(); ?>
			<div id="inavii-admin-app-loader" class="inavii-admin-loader" role="status" aria-live="polite">
				<img loading="lazy" src="<?php echo esc_url( $loaderUrl ); ?>" alt="<?php esc_attr_e( 'Loading', 'inavii-social-feed' ); ?>" />
			</div>
		<?php endif; ?>
		<?php
	}

	public function highlight_parent_menu( $parent_file ): string {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( in_array( $page, [ 'inavii-instagram-settings', 'inavii-instagram-settings-debug' ], true ) ) {
			return 'inavii-instagram-settings';
		}

		return is_string( $parent_file ) ? $parent_file : '';
	}

	public function highlight_sub_menu( $submenu_file ): string {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( $page === 'inavii-instagram-settings-debug' ) {
			return '';
		}

		if ( $page === 'inavii-instagram-settings' ) {
			if ( Plugin::isLegacyUi() ) {
				return 'inavii-instagram-settings';
			}

			return 'inavii-instagram-settings&screen=settings';
		}

		return is_string( $submenu_file ) ? $submenu_file : '';
	}

	public function hide_debug_submenu(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<style>
			#toplevel_page_inavii-instagram-settings .wp-submenu a[href="admin.php?page=inavii-instagram-settings-debug"] { display: none; }
		</style>';
	}

	public function print_admin_app_shell_styles(): void {
		if ( ! $this->is_settings_page() ) {
			return;
		}

		echo '<style>
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .updated,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .notice,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .error,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .update-nag,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .woodmart-msg,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .dup-updated,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .sbi_notice,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .sp-eafree-review-notice,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > #kadence-notice-starter-templates,
			body.toplevel_page_inavii-instagram-settings #wpbody-content > .toucan-welcome-panel,
			body.toplevel_page_inavii-instagram-settings .loginpress-review-notice,
			body.toplevel_page_inavii-instagram-settings #beacon-container,
			body.toplevel_page_inavii-instagram-settings #ehe-admin-cb,
			body.toplevel_page_inavii-instagram-settings #admin_banner_about_automatic_media_detection,
			body.toplevel_page_inavii-instagram-settings #wpbody-content .fs-notice {
				display: none !important;
			}

			#inavii-social-feed-app {
        height: 100%;
				overflow: hidden;
				position: relative;
			}
			
			#inavii-social-feed-app:not(.inavii-social-feed-legacy) {
				max-height: calc(100vh - 32px);
			}

			@media (max-width: 782px) {
				#inavii-social-feed-app {
					min-height: calc(100vh - 46px);
				}
			}
		</style>';

		if ( $this->shouldRenderV3Loader() ) {
			echo '<style>
				#inavii-admin-app-loader.inavii-admin-loader {
					align-items: center;
					background: #ffffff;
					opacity: 1;
					display: flex;
					inset: 32px 0 0 160px;
					justify-content: center;
					pointer-events: all;
					position: fixed;
					transition: opacity 220ms ease-in-out, visibility 220ms ease-in-out;
					z-index: 9998;
				}

			html.inavii-admin-app-ready #inavii-admin-app-loader.inavii-admin-loader {
				opacity: 0;
				pointer-events: none;
				visibility: hidden;
			}

			@media (max-width: 960px) {
				#inavii-admin-app-loader.inavii-admin-loader {
					inset: 32px 0 0 36px;
				}
			}

			@media (max-width: 782px) {
				#inavii-social-feed-app {
					min-height: calc(100vh - 46px);
				}

				#inavii-admin-app-loader.inavii-admin-loader {
					inset: 46px 0 0 0;
				}
			}
		</style>';
		}
	}

	private function is_settings_page(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		return $page === 'inavii-instagram-settings';
	}

	private function loaderUrl(): string {
		$assetsPath = rtrim( (string) Env::$assets_path, '/' );
		$assetsUrl  = rtrim( (string) Env::$assets_url, '/' );

		if ( is_file( $assetsPath . '/' . self::LOADER_FILE ) ) {
			return $assetsUrl . '/' . self::LOADER_FILE;
		}

		return rtrim( INAVII_INSTAGRAM_URL, '/' ) . '/includes/Legacy/Assets/react/static/media/inavii-loading.feb657a3921464e5b53c.gif';
	}

	private function shouldRenderV3Loader(): bool {
		return ! Plugin::isLegacyUi();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
