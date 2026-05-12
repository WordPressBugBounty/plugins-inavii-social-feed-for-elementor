<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Admin;

use Inavii\Instagram\Config\Plugin;

final class ReviewNotice {
	public const DISMISS_OPTION       = 'inavii_social_feed_review_notice_dismissed';
	public const DISMISS_AJAX_ACTION  = 'inavii_social_feed_dismiss_review_notice';
	public const DISMISS_POST_ACTION  = 'inavii_social_feed_dismiss_review_notice_post';
	public const DISMISS_NONCE_ACTION = 'inavii_social_feed_dismiss_feedback_notice';
	public const REVIEW_URL           = 'https://wordpress.org/support/plugin/inavii-social-feed-for-elementor/reviews/?filter=5';
	private ReviewNoticePolicy $policy;

	public function __construct( ?ReviewNoticePolicy $policy = null ) {
		$this->policy = $policy ?? new ReviewNoticePolicy();
	}

	public function register(): void {
		if ( ! is_admin() || is_network_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'checkInstallationDate' ] );
		add_action( 'wp_ajax_' . self::DISMISS_AJAX_ACTION, [ $this, 'dismissNotice' ] );
		add_action( 'admin_post_' . self::DISMISS_POST_ACTION, [ $this, 'dismissNoticeAndRedirect' ] );
		add_action( 'admin_head', [ $this, 'printStyle' ] );
		add_action( 'admin_footer', [ $this, 'printDismissScript' ] );
	}

	public function checkInstallationDate(): void {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		add_action( 'admin_notices', [ $this, 'renderNotice' ] );
	}

	public function renderNotice(): void {
		if ( ! $this->shouldRenderNotice() ) {
			return;
		}

		$this->displayNotice();
	}

	public function displayNotice(): void {
		$displayName = $this->policy->displayName();
		$pluginName  = Plugin::name();
		$dismissUrl  = $this->dismissUrl( $this->currentAdminPageUrl() );
		$reviewUrl   = self::REVIEW_URL;

		?>
		<div class="notice notice-info inavii-review-notice">
			<div class="inavii-review-notice-logo"></div>
			<?php
			printf(
				/* translators: 1: Current admin display name, 2: Plugin name. */
				wp_kses_post( __( "Hey %1\$s, it's Maciej Sromek from %2\$s. You have used this free plugin for some time now, and I hope you like it!", 'inavii-social-feed' ) ),
				'<strong>' . esc_html( $displayName ) . '</strong>',
				'<strong>' . esc_html( $pluginName ) . '</strong>'
			);
			?>
			<br/>
			<?php
				printf(
					/* translators: 1: Opening review link tag, 2: Closing review link tag. */
					wp_kses_post( __( 'The Inavii team have spent countless hours developing it, and it would mean a lot to me if you %1$ssupport it with a quick review on WordPress.org.%2$s', 'inavii-social-feed' ) ),
					'<strong><a target="_blank" rel="noopener noreferrer" href="' . esc_url( self::REVIEW_URL ) . '">',
					'</a></strong>'
				);
			?>
			<div class="button-container">
				<a href="<?php echo esc_url( $reviewUrl ); ?>" target="_blank" rel="noopener noreferrer" data-link="follow"
					class="button-secondary js-inavii-review-notice-dismiss">
						<span class="dashicons dashicons-star-filled"></span>
						<?php
						printf(
							/* translators: %s: Plugin name. */
							esc_html__( 'Review %s', 'inavii-social-feed' ),
							esc_html( $pluginName )
						);
						?>
				</a>
				<a href="<?php echo esc_url( $dismissUrl ); ?>" class="button-secondary js-inavii-review-notice-dismiss inavii-review-notice-dismiss">
					<span class="dashicons dashicons-no-alt"></span>
					<?php esc_html_e( 'No thanks', 'inavii-social-feed' ); ?>
				</a>
			</div>
			<a href="<?php echo esc_url( $dismissUrl ); ?>" class="inavii-review-notice-dismiss-x js-inavii-review-notice-dismiss"
				aria-label="<?php esc_attr_e( 'Close', 'inavii-social-feed' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
					<path
						d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path>
				</svg>
			</a>
		</div>
		<?php
	}

	private function shouldRenderNotice(): bool {
		if ( ! $this->allowDisplay() ) {
			return false;
		}

		return $this->policy->shouldShow();
	}

	public function dismissNotice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '-1', 403 );
		}

		check_ajax_referer( self::DISMISS_NONCE_ACTION );

		update_option( self::DISMISS_OPTION, 1, false );

		wp_send_json_success();
	}

	public function dismissNoticeAndRedirect(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '-1', 403 );
		}

		check_admin_referer( self::DISMISS_NONCE_ACTION );

		update_option( self::DISMISS_OPTION, 1, false );

		$requestedRedirect = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : '';
		$requestedRedirect = is_string( $requestedRedirect ) ? trim( $requestedRedirect ) : '';
		$fallbackRedirect  = wp_get_referer();
		$fallbackRedirect  = is_string( $fallbackRedirect ) && $fallbackRedirect !== '' ? $fallbackRedirect : admin_url();

		$redirectTo = $requestedRedirect !== ''
			? wp_validate_redirect( $requestedRedirect, $fallbackRedirect )
			: $fallbackRedirect;

		wp_safe_redirect( $redirectTo );
		exit;
	}

	public function printDismissScript(): void {
		if ( ! $this->allowDisplay() ) {
			return;
		}

		$nonce      = wp_create_nonce( self::DISMISS_NONCE_ACTION );
		$ajaxAction = self::DISMISS_AJAX_ACTION;
		$ajaxUrl    = admin_url( 'admin-ajax.php' );
		?>
		<script>
			(function ($) {
				$(document).on('click', '.js-inavii-review-notice-dismiss', function (event) {
					var $box = $(this).closest('.inavii-review-notice');
					var isLink = $(this).attr('data-link') === 'follow';
					var targetUrl = $(this).attr('href') || '';
					var opensInNewTab = $(this).attr('target') === '_blank';
					var requestData = {
						_wpnonce: '<?php echo esc_js( $nonce ); ?>',
						action: '<?php echo esc_js( $ajaxAction ); ?>'
					};

					$box.fadeOut(700);

						if (isLink && opensInNewTab && targetUrl) {
							$.ajax({
								url: '<?php echo esc_js( $ajaxUrl ); ?>',
								type: 'POST',
								data: requestData
							}).always(function () {
								setTimeout(function () {
									$box.remove();
								}, 700);

							});

							return;
						}

					$.ajax({
						url: '<?php echo esc_js( $ajaxUrl ); ?>',
						type: 'POST',
						data: requestData
					}).always(function () {
						setTimeout(function () {
							$box.remove();
						}, 700);

					});

					event.preventDefault();
				});
			}(jQuery));
		</script>
		<?php
	}

	public function printStyle(): void {
		if ( ! $this->allowDisplay() ) {
			return;
		}

		$logo = esc_url_raw( $this->policy->logoUrl() );
		ob_start();
		?>
<style id="inavii-social-feed-review-notice-style">
.inavii-review-notice .button-container {
	display: block;
	width: 100%;
	margin: .75em 0 1.5em 0;
}


.inavii-review-notice .button-container .dashicons {
	font-size: 14px;
	line-height: 1.9;
	margin-left: -4px;
}

.inavii-review-notice .button-container .dashicons-star-filled {
	color: #E6B800;
}

.inavii-review-notice .button-container .dashicons-no-alt {
	color: rgb(220, 58, 58);
	line-height: 2;
}

.inavii-review-notice .button-container a {
	margin-right: 10px;
}

.notice.inavii-review-notice {
	position: relative;
	border-left: 4px solid #00a0d2;
	background: #fefefe;
	padding: 10px 50px 1px 12px;
	box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
	-webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
	margin: 15px 0 20px 0;
}

.inavii-review-notice .inavii-review-notice-logo {
	float: left;
	margin-top: 2px;
	margin-right: 15px;
	margin-left: 8px;
	font-size: 14px;
	height: 82px;
	text-align: center;
	width: 82px;
	background-repeat: no-repeat;
	background-position: center center;
	background-size: 78px;
	background-color: #fff;
	border-radius: 50%;
	box-shadow: inset 0 0 0 1px #d7dee6;
	background-image: url('<?php echo esc_url_raw( $logo ); ?>');
}

.inavii-review-notice .inavii-review-notice-dismiss-x {
	background: none;
	border: none;
	cursor: pointer;
	height: 24px;
	position: absolute;
	right: 5px;
	top: 10px;
}
</style>
		<?php
		$styleTag = (string) ob_get_clean();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static CSS with escaped logo URL.
		echo $styleTag;
	}

	private function allowDisplay(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return true;
		}

		$currentScreen = get_current_screen();
		if ( ! is_object( $currentScreen ) || ! isset( $currentScreen->base ) ) {
			return true;
		}

		$base = (string) $currentScreen->base;
		if ( $base === '' ) {
			return true;
		}

		return in_array( $base, [ 'dashboard', 'post', 'edit' ], true );
	}

	private function dismissUrl( string $redirectTo ): string {
		$url = add_query_arg(
			[
				'action'      => self::DISMISS_POST_ACTION,
				'redirect_to' => $redirectTo,
			],
			admin_url( 'admin-post.php' )
		);

		return (string) add_query_arg( '_wpnonce', wp_create_nonce( self::DISMISS_NONCE_ACTION ), $url );
	}

	private function currentAdminPageUrl(): string {
		$requestUri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! is_string( $requestUri ) || trim( $requestUri ) === '' ) {
			return admin_url();
		}

		return home_url( $requestUri );
	}
}
