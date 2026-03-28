<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Integration\Elementor;

final class ElementorEditorContext {
	public function registerPreviewScriptHooks( callable $callback ): void {
		if ( ! $this->isElementorAvailable() ) {
			return;
		}

		add_action( 'elementor/frontend/after_enqueue_scripts', $callback, 20 );
		add_action( 'elementor/preview/enqueue_scripts', $callback, 20 );
	}

	public function isEditorContext(): bool {
		if ( ! $this->isElementorAvailable() ) {
			return false;
		}

		try {
			$elementor = \Elementor\Plugin::$instance;

			if ( isset( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
				return true;
			}

			if ( isset( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
				return true;
			}
		} catch ( \Throwable $e ) {
			return false;
		}

		return false;
	}

	private function isElementorAvailable(): bool {
		return defined( 'ELEMENTOR_VERSION' ) && class_exists( '\\Elementor\\Plugin' );
	}
}
