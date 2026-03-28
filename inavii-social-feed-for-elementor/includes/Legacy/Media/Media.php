<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Media;

use Inavii\Instagram\Config\Env;
use Inavii\Instagram\Media\Files\MediaFileRemover;

/**
 * Legacy media helper used by v2/old Elementor flow.
 */
class Media {
	public const DESTINATION = DIRECTORY_SEPARATOR . 'inavii-social-feed' . DIRECTORY_SEPARATOR;

	public const IMAGE_SMALL  = [ 's' => 300 ];
	public const IMAGE_MEDIUM = [ 'm' => 768 ];
	public const IMAGE_LARGE  = [ 'l' => 1024 ];

	protected const IMAGE_TYPE = '.jpg';

	public function __construct() {
		if ( ! function_exists( 'wp_mkdir_p' ) ) {
			require_once ABSPATH . 'wp-includes/functions.php';
		}

		$this->createDirectory();
	}

	public static function checkGDLibraryAvailability(): bool {
		return function_exists( 'gd_info' ) && extension_loaded( 'gd' );
	}

	public static function baseDir(): string {
		if ( Env::$media_dir !== '' ) {
			return rtrim( (string) Env::$media_dir, '/\\' ) . DIRECTORY_SEPARATOR;
		}

		$uploadDir = wp_upload_dir();
		$baseDir   = isset( $uploadDir['basedir'] ) ? (string) $uploadDir['basedir'] : '';

		return rtrim( $baseDir, '/\\' ) . self::DESTINATION;
	}

	public static function baseUrl(): string {
		if ( Env::$media_url !== '' ) {
			return rtrim( (string) Env::$media_url, '/' ) . '/';
		}

		$uploadDir = wp_upload_dir();
		$baseUrl   = isset( $uploadDir['baseurl'] ) ? (string) $uploadDir['baseurl'] : '';

		return rtrim( $baseUrl, '/' ) . '/' . ltrim( str_replace( '\\', '/', self::DESTINATION ), '/' );
	}

	public static function assetImageUrl( string $imageName ): string {
		return INAVII_INSTAGRAM_URL . 'assets/images/' . ltrim( str_replace( '\\', '/', $imageName ), '/' );
	}

	public function getImageDir( string $mediaId ): string {
		return self::baseDir() . $mediaId;
	}

	public function getImageUrl( int $mediaId ): string {
		return self::baseUrl() . $mediaId . '/';
	}

	public static function mediaUrl( string $mediaId ): array {
		$path = self::baseUrl() . $mediaId;

		return self::mediaPath( $path );
	}

	public static function mediaDir( string $mediaId ): array {
		$path = ( new self() )->getImageDir( $mediaId );

		return self::mediaPath( $path );
	}

	public static function deleteImage( string $mediaId, string $size ): void {
		if ( self::checkGDLibraryAvailability() === false && $size === 'full' ) {
			return;
		}

		$paths = self::mediaDir( $mediaId );
		$path  = isset( $paths[ $size ] ) ? (string) $paths[ $size ] : '';
		if ( $path === '' ) {
			return;
		}

		( new MediaFileRemover() )->deletePath( $path );
	}

	public static function delete( string $mediaId ): void {
		$paths   = self::mediaDir( $mediaId );
		$remover = new MediaFileRemover();

		foreach ( $paths as $path ) {
			$remover->deletePath( (string) $path );
		}
	}

	public static function imageExist( string $id ): bool {
		return file_exists( ( new self() )->getImageDir( $id ) . self::IMAGE_TYPE );
	}

	public static function mediaExist( string $id, string $mediaSize ): bool {
		return file_exists( ( new self() )->getImageDir( $id ) . $mediaSize . self::IMAGE_TYPE );
	}

	public static function deleteMediaDirectory(): void {
		( new MediaFileRemover() )->deleteMediaDirectory();
	}

	private function createDirectory(): void {
		$dir = self::baseDir();

		if ( is_dir( $dir ) ) {
			return;
		}

		wp_mkdir_p( $dir );
	}

	private static function mediaPath( string $path ): array {
		return [
			'full'   => $path . self::IMAGE_TYPE,
			'medium' => $path . '-m' . self::IMAGE_TYPE,
			'large'  => $path . '-l' . self::IMAGE_TYPE,
			'small'  => $path . '-s' . self::IMAGE_TYPE,
		];
	}
}

