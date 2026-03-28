<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

use Inavii\Instagram\Config\Env;

final class MediaFileRemover {

	public function deleteFromUrl( string $url ): void {
		$path = $this->resolveLocalPath( $url );
		if ( $path === '' ) {
			return;
		}

		$this->deletePath( $path );
	}

	public function deletePath( string $path ): void {
		$path = trim( $path );
		if ( $path === '' ) {
			return;
		}

		if ( $this->isAbsolutePath( $path ) ) {
			$this->deleteFile( $path );
			return;
		}

		$base = Env::$uploads_dir;
		if ( $base === '' ) {
			$upload = wp_get_upload_dir();
			$base   = isset( $upload['basedir'] ) ? (string) $upload['basedir'] : '';
		}

		if ( $base === '' ) {
			return;
		}

		$full = rtrim( $base, '/\\' ) . '/' . ltrim( $path, '/\\' );
		$this->deleteFile( $full );
	}

	/**
	 * @param string[] $paths
	 */
	public function deletePaths( array $paths ): void {
		foreach ( $paths as $path ) {
			$this->deletePath( (string) $path );
		}
	}

	public function deleteMediaDirectory(): void {
		$dir = Env::$media_dir;
		if ( $dir === '' ) {
			$upload  = wp_get_upload_dir();
			$baseDir = isset( $upload['basedir'] ) ? (string) $upload['basedir'] : '';
			if ( $baseDir !== '' ) {
				$dir = rtrim( $baseDir, '/\\' ) . '/inavii-social-feed';
			}
		}

		if ( $dir === '' || ! is_dir( $dir ) ) {
			return;
		}

		if ( basename( $dir ) !== 'inavii-social-feed' ) {
			return;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return;
		}

		$wp_filesystem->delete( $dir, true );
	}

	private function deleteFile( string $path ): void {
		if ( $path === '' || ! is_file( $path ) ) {
			return;
		}

		if ( ! $this->isPathAllowed( $path ) ) {
			return;
		}
		wp_delete_file( $path );
	}

	private function isPathAllowed( string $path ): bool {
		if ( $path === '' ) {
			return false;
		}

		$path    = wp_normalize_path( $path );
		$allowed = [];

		$mediaDir = rtrim( wp_normalize_path( Env::$media_dir ), '/' );
		if ( $mediaDir !== '' ) {
			$allowed[] = $mediaDir;
		}

		$upload  = wp_get_upload_dir();
		$baseDir = isset( $upload['basedir'] ) ? wp_normalize_path( (string) $upload['basedir'] ) : '';
		if ( $baseDir !== '' ) {
			$allowed[] = rtrim( $baseDir, '/' ) . '/inavii-social-feed';
		}

		foreach ( $allowed as $dir ) {
			$dir = rtrim( $dir, '/' );
			if ( $dir !== '' && strpos( $path, $dir . '/' ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	private function isAbsolutePath( string $path ): bool {
		if ( $path === '' ) {
			return false;
		}

		if ( $path[0] === '/' || $path[0] === '\\' ) {
			return true;
		}

		return (bool) preg_match( '/^[A-Za-z]:[\\\\\\/]/', $path );
	}

	private function resolveLocalPath( string $url ): string {
		$url = trim( $url );
		if ( $url === '' ) {
			return '';
		}

		$mediaUrl = rtrim( Env::$media_url, '/' );
		if ( $mediaUrl !== '' && strpos( $url, $mediaUrl ) === 0 ) {
			$suffix = ltrim( substr( $url, strlen( $mediaUrl ) ), '/' );
			if ( $suffix === '' ) {
				return '';
			}

			$baseDir = rtrim( Env::$media_dir, '/\\' );
			if ( $baseDir !== '' ) {
				return $baseDir . '/' . $suffix;
			}

			return 'inavii-social-feed/' . $suffix;
		}

		$uploadsUrl = rtrim( Env::$uploads_url, '/' );
		if ( $uploadsUrl !== '' && strpos( $url, $uploadsUrl ) === 0 ) {
			$suffix = ltrim( substr( $url, strlen( $uploadsUrl ) ), '/' );
			if ( $suffix === '' || strpos( $suffix, 'inavii-social-feed/' ) !== 0 ) {
				return '';
			}

			$baseDir = rtrim( Env::$uploads_dir, '/\\' );
			if ( $baseDir !== '' ) {
				return $baseDir . '/' . $suffix;
			}

			return $suffix;
		}

		if ( strpos( $url, '://' ) === false ) {
			$relative = ltrim( $url, '/\\' );
			if ( strpos( $relative, 'inavii-social-feed/' ) === 0 ) {
				return $relative;
			}
		}

		return '';
	}
}
