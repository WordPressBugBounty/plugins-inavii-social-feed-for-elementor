<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Application;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Config\Env;
use Inavii\Instagram\Config\Troubleshooting\HealthDiagnostics;
use Inavii\Instagram\Front\Domain\Policy\ReconnectNoticePolicy;

final class GlobalReconnectNoticeRenderer {
	private const DISMISS_STORAGE_KEY = 'inaviiSocialFeedReconnectNoticeDismissed';

	private HealthDiagnostics $health;
	private ReconnectNoticePolicy $noticePolicy;
	private static bool $didRender = false;

	public function __construct(
		HealthDiagnostics $health,
		ReconnectNoticePolicy $noticePolicy
	) {
		$this->health       = $health;
		$this->noticePolicy = $noticePolicy;
	}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'renderInFooter' ], 4 );

		// Reconnect state changes whenever account auth status changes.
		add_action( 'inavii/social-feed/account/connected', [ $this, 'flushReconnectCache' ], 20 );
		add_action( 'inavii/social-feed/account/deleted', [ $this, 'flushReconnectCache' ], 20 );
		add_action( 'inavii/social-feed/media/sync/error', [ $this, 'flushReconnectCache' ], 20 );
		add_action( 'inavii/social-feed/media/sync/finished', [ $this, 'flushReconnectCache' ], 20 );
	}

	public function renderInFooter(): void {
		if ( self::$didRender ) {
			return;
		}

		if ( ! $this->canRenderOnRequest() ) {
			return;
		}

		$notice = $this->resolveNotice();
		if ( $notice === [] ) {
			return;
		}

		self::$didRender = true;

		$this->renderStyle();
		$this->renderMarkup( $notice );
		$this->renderScript();
	}

	public function flushReconnectCache(): void {
		$this->health->flushReconnectRequiredAccountsCache();
	}

	private function resolveNotice(): array {
		$count = $this->health->countReconnectRequiredAccounts();
		return $this->noticePolicy->buildNotice( $count, $this->resolveAccountsSettingsUrl() );
	}

	private function resolveAccountsSettingsUrl(): string {
		if ( ! function_exists( 'admin_url' ) ) {
			return '';
		}

		if ( Plugin::isLegacyUi() ) {
			return admin_url( 'admin.php?page=inavii-instagram-settings#/accounts' );
		}

		return admin_url( 'admin.php?page=inavii-instagram-settings&screen=accounts' );
	}

	private function canRenderOnRequest(): bool {
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return false;
		}

		return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
	}

	private function renderMarkup( array $notice ): void {
		$title   = isset( $notice['title'] ) ? trim( (string) $notice['title'] ) : '';
		$message = isset( $notice['message'] ) ? trim( (string) $notice['message'] ) : '';
		$link    = isset( $notice['link'] ) ? trim( (string) $notice['link'] ) : '';
		$iconUrl = $this->instagramIconUrl();

		if ( $title === '' ) {
			$title = 'Accounts require reconnect';
		}

		echo '<div id="inavii-social-feed-global-notice" class="inavii-admin-notice" role="alert" data-inavii-global-reconnect-notice>';
		echo '<button type="button" class="inavii-admin-notice__close" aria-label="Dismiss notice" data-inavii-dismiss-reconnect-notice>&times;</button>';
		echo '<img loading="lazy" class="inavii-admin-notice__icon" src="' . esc_url( $iconUrl ) . '" alt="" aria-hidden="true" />';
		echo '<div class="inavii-admin-notice__content">';
		echo '<div class="inavii-admin-notice__title">' . esc_html( $title ) . '</div>';
		if ( $message !== '' || $link !== '' ) {
			echo '<div class="inavii-admin-notice__message">';
			echo esc_html( $message );
			if ( $link !== '' ) {
				echo ' <a class="inavii-admin-notice__link" href="' . esc_url( $link ) . '">Resolve this issue</a>.';
			}
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	private function renderStyle(): void {
		$css = <<<'CSS'
.inavii-admin-notice {
	position: fixed;
	right: 16px;
	bottom: 16px;
	width: min(425px, calc(100vw - 32px));
	display: flex;
	align-items: flex-start;
	gap: 10px;
	background: #fff;
	color: #1f1f1f;
	padding: 12px 34px 12px 12px;
	border-radius: 10px;
	margin: 0;
	z-index: 2147483000;
	box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
}

.inavii-admin-notice__icon {
	width: 49px;
	height: 49px;
	border-radius: 8px;
	flex: 0 0 auto;
}

.inavii-admin-notice__content {
	min-width: 0;
	flex: 1 1 auto;
}

.inavii-admin-notice__title {
	margin: 0 0 5px;
	font-size: 15px;
	line-height: 1.2;
	font-weight: 700;
}

.inavii-admin-notice__message {
	margin: 0;
	color: #696969;
	font-size: 11px;
	line-height: 1.3;
}

.inavii-admin-notice__link {
	color: inherit;
	text-decoration: underline;
	text-underline-offset: 2px;
	font-weight: 500;
}

.inavii-admin-notice__close {
	position: absolute;
	top: 5px;
	right: 7px;
	border: 0;
	background: transparent;
	color: #8d8d8d;
	font-size: 20px;
	line-height: 1;
	width: 24px;
	height: 24px;
	cursor: pointer;
	padding: 0;
}

.inavii-admin-notice__close:hover {
	color: #4b4b4b;
}

@media (max-width: 767px) {
	.inavii-admin-notice {
		right: 12px;
		left: 12px;
		bottom: 12px;
		width: auto;
		gap: 8px;
		padding: 10px 30px 10px 10px;
	}

	.inavii-admin-notice__icon {
		width: 39px;
		height: 39px;
	}

	.inavii-admin-notice__title {
		font-size: 14px;
		margin-bottom: 3px;
	}

	.inavii-admin-notice__message {
		font-size: 10px;
		line-height: 1.25;
	}

	.inavii-admin-notice__close {
		top: 3px;
		right: 5px;
		font-size: 17px;
		width: 20px;
		height: 20px;
	}
}

@media (min-width: 768px) and (max-width: 1280px) {
	.inavii-admin-notice {
		width: min(390px, calc(100vw - 32px));
	}
}

@media (min-width: 1281px) {
	.inavii-admin-notice {
		width: min(425px, calc(100vw - 32px));
	}
}

@media (prefers-reduced-motion: no-preference) {
	.inavii-admin-notice {
		animation: inavii-admin-notice-enter 220ms ease-out;
	}
}

@keyframes inavii-admin-notice-enter {
	from {
		opacity: 0;
		transform: translateY(10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
CSS;

		if ( function_exists( 'wp_print_inline_style_tag' ) ) {
			wp_print_inline_style_tag(
				$css,
				[
					'id' => 'inavii-social-feed-global-notice-style',
				]
			);
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static CSS string printed in style tag.
		echo '<style id="inavii-social-feed-global-notice-style">' . $css . '</style>';
	}

	private function renderScript(): void {
		$storageKey = esc_js( self::DISMISS_STORAGE_KEY );
		$script     = <<<JS
(function () {
	var root = document.getElementById('inavii-social-feed-global-notice');
	if (!root) {
		return;
	}

	try {
		if (window.sessionStorage && window.sessionStorage.getItem('{$storageKey}') === '1') {
			root.remove();
			return;
		}
	} catch (e) {}

	var button = root.querySelector('[data-inavii-dismiss-reconnect-notice]');
	if (!button) {
		return;
	}

	button.addEventListener('click', function () {
		root.remove();
		try {
			if (window.sessionStorage) {
				window.sessionStorage.setItem('{$storageKey}', '1');
			}
		} catch (e) {}
	});
})();
JS;

		if ( function_exists( 'wp_print_inline_script_tag' ) ) {
			wp_print_inline_script_tag(
				$script,
				[
					'id' => 'inavii-social-feed-global-notice-script',
				]
			);
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static script string printed in script tag.
		echo '<script id="inavii-social-feed-global-notice-script">' . $script . '</script>';
	}

	private function instagramIconUrl(): string {
		return rtrim( (string) Env::$assets_url, '/' ) . '/images/instagram-avatar-fallback.svg';
	}
}
