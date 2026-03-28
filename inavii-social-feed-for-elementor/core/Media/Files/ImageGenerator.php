<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

final class ImageGenerator {

	/**
	 * @return string Actual extension used (webp|jpg)
	 */
	public function generate(
		string $sourceFile,
		string $targetFile,
		int $width,
		string $preferredExt = 'webp',
		string $context = 'main'
	): string {
		$this->ensureImageEditorLoaded();

		$editor = \wp_get_image_editor( $sourceFile );

		if ( \is_wp_error( $editor ) ) {
			throw new \RuntimeException( 'Image editor error: ' . $editor->get_error_message() );
		}

		$size       = $editor->get_size();
		$origWidth  = isset( $size['width'] ) ? (int) $size['width'] : 0;
		$origHeight = isset( $size['height'] ) ? (int) $size['height'] : 0;

		if ( $origWidth <= 0 || $origHeight <= 0 ) {
			throw new \RuntimeException( 'Resize error: Could not calculate resized image dimensions' );
		}

		if ( $width > 0 && $width < $origWidth ) {
			$resized = $editor->resize( $width, null, false );
			if ( \is_wp_error( $resized ) ) {
				throw new \RuntimeException( 'Resize error: ' . $resized->get_error_message() );
			}
		}

		$quality = apply_filters( 'inavii/social-feed/media/image_quality', 80, $context, $preferredExt );
		if ( is_numeric( $quality ) ) {
			$quality = (int) $quality;
			if ( $quality > 0 && $quality <= 100 ) {
				$editor->set_quality( $quality );
			}
		}

		$preferredExt = $this->normalizeExtension( $preferredExt );
		$mime         = $preferredExt === 'webp' ? 'image/webp' : 'image/jpeg';

		$saved = $editor->save( $targetFile, $mime );
		if ( ! \is_wp_error( $saved ) ) {
			return $preferredExt;
		}

		if ( $preferredExt !== 'webp' ) {
			throw new \RuntimeException( 'Save error: ' . $saved->get_error_message() );
		}

		$fallbackFile = $this->replaceExtension( $targetFile, 'jpg' );
		$saved        = $editor->save( $fallbackFile, 'image/jpeg' );
		if ( \is_wp_error( $saved ) ) {
			throw new \RuntimeException( 'Save error: ' . $saved->get_error_message() );
		}

		return 'jpg';
	}

	private function ensureImageEditorLoaded(): void {
		if ( ! \function_exists( 'wp_get_image_editor' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
	}

	private function normalizeExtension( string $ext ): string {
		$ext = strtolower( trim( $ext ) );
		if ( $ext === '' ) {
			return 'webp';
		}
		if ( $ext[0] === '.' ) {
			$ext = substr( $ext, 1 );
		}
		if ( $ext !== 'webp' && $ext !== 'jpg' && $ext !== 'jpeg' ) {
			return 'webp';
		}
		return $ext === 'jpeg' ? 'jpg' : $ext;
	}

	private function replaceExtension( string $path, string $ext ): string {
		return preg_replace( '/\.[a-z0-9]+$/i', '.' . $ext, $path ) ?? $path;
	}
}
