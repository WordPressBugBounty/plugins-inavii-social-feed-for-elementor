<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

use Inavii\Instagram\Config\Env;

final class MediaPaths {

	/**
	 * Save directly under: wp-content/uploads/inavii-social-feed/
	 *
	 * @return array{0:string,1:string} [mainAbs, mainRel]
	 */
	public function buildMainPaths( string $igMediaId, string $extension ): array {
		$uploadBaseDir = Env::$uploads_dir;

		if ( $uploadBaseDir === '' || ! \is_dir( $uploadBaseDir ) ) {
			throw new \RuntimeException( 'Uploads base directory not available' );
		}

		$baseDir = Env::$media_dir !== '' ? Env::$media_dir : ( $uploadBaseDir . '/inavii-social-feed' );
		$baseDir = \rtrim( $baseDir, '/\\' );
		if ( ! \is_dir( \dirname( $baseDir ) ) ) {
			throw new \RuntimeException( 'Uploads base directory not available' );
		}

		$uploadBaseDir = \rtrim( $uploadBaseDir, '/\\' );
		$relDir        = '';
		if ( \strpos( $baseDir, $uploadBaseDir . '/' ) === 0 ) {
			$relDir = \substr( $baseDir, \strlen( $uploadBaseDir ) + 1 );
		}
		if ( $relDir === '' ) {
			$relDir = 'inavii-social-feed';
		}

		$fileBase = $this->safeFileName( $igMediaId );

		$ext = $this->normalizeExtension( $extension );

		$mainRel = ( $relDir !== '' ? $relDir . '/' : '' ) . $fileBase . '.' . $ext;
		$mainAbs = $baseDir . '/' . $fileBase . '.' . $ext;

		return [ $mainAbs, $mainRel ];
	}

	/**
	 * Save directly under: wp-content/uploads/inavii-social-feed/
	 *
	 * @return array{0:string,1:string} [thumbAbs, thumbRel]
	 */
	public function buildThumbPaths( string $igMediaId, string $extension ): array {
		$uploadBaseDir = Env::$uploads_dir;

		if ( $uploadBaseDir === '' || ! \is_dir( $uploadBaseDir ) ) {
			throw new \RuntimeException( 'Uploads base directory not available' );
		}

		$baseDir = Env::$media_dir !== '' ? Env::$media_dir : ( $uploadBaseDir . '/inavii-social-feed' );
		$baseDir = \rtrim( $baseDir, '/\\' );
		if ( ! \is_dir( \dirname( $baseDir ) ) ) {
			throw new \RuntimeException( 'Uploads base directory not available' );
		}

		$uploadBaseDir = \rtrim( $uploadBaseDir, '/\\' );
		$relDir        = '';
		if ( \strpos( $baseDir, $uploadBaseDir . '/' ) === 0 ) {
			$relDir = \substr( $baseDir, \strlen( $uploadBaseDir ) + 1 );
		}
		if ( $relDir === '' ) {
			$relDir = 'inavii-social-feed';
		}

		$fileBase = $this->safeFileName( $igMediaId );
		$ext      = $this->normalizeExtension( $extension );

		$thumbRel = ( $relDir !== '' ? $relDir . '/' : '' ) . $fileBase . '-thumb.' . $ext;
		$thumbAbs = $baseDir . '/' . $fileBase . '-thumb.' . $ext;

		return [ $thumbAbs, $thumbRel ];
	}

	public function preferredExtension( string $context = 'main' ): string {
		$ext = apply_filters( 'inavii/social-feed/media/image_extension', 'webp', $context );
		$ext = $this->normalizeExtension( is_string( $ext ) ? $ext : 'webp' );
		if ( $ext === 'webp' && ! $this->supportsWebp() ) {
			return 'jpg';
		}
		return $ext;
	}

	public function ensureDirectoryForFile( string $filePath ): void {
		$dir = \dirname( $filePath );
		if ( \is_dir( $dir ) ) {
			return;
		}

		if ( ! \function_exists( 'wp_mkdir_p' ) ) {
			require_once ABSPATH . 'wp-includes/functions.php';
		}

		if ( ! \wp_mkdir_p( $dir ) ) {
			throw new \RuntimeException( 'Failed to create directory: ' . $dir );
		}
	}

	private function safeFileName( string $name ): string {
		$name = \sanitize_file_name( $name );
		if ( $name === '' ) {
			$name = 'file-' . (string) \time();
		}
		return $name;
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

	private function supportsWebp(): bool {
		if ( \function_exists( 'imagewebp' ) ) {
			return true;
		}

		if ( \class_exists( '\Imagick' ) ) {
			try {
				$formats = \Imagick::queryFormats( 'WEBP' );
				return \is_array( $formats ) && $formats !== [];
			} catch ( \Throwable $e ) {
				return false;
			}
		}

		return false;
	}
}
