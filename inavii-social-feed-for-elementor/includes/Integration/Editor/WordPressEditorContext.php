<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Integration\Editor;

final class WordPressEditorContext {
	public function isEditorContext(): bool {
		if ( $this->isBlockEditorScreen() ) {
			return true;
		}

		return $this->isBlockRendererRequest();
	}

	private function isBlockEditorScreen(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! method_exists( $screen, 'is_block_editor' ) ) {
			return false;
		}

		return (bool) $screen->is_block_editor();
	}

	private function isBlockRendererRequest(): bool {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$restRoute = isset( $_REQUEST['rest_route'] ) ? (string) $_REQUEST['rest_route'] : '';
		if ( strpos( $restRoute, '/wp/v2/block-renderer/' ) !== false ) {
			return true;
		}

		$requestUri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

		return strpos( $requestUri, '/wp/v2/block-renderer/' ) !== false;
	}
}
