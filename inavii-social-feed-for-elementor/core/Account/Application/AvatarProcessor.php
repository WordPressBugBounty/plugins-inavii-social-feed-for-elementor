<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Application;

use Inavii\Instagram\Config\Env;
use Inavii\Instagram\Media\Application\MediaFileService;

class AvatarProcessor {
	private const WIDTH     = 80;
	private const EXTENSION = 'webp';

	private MediaFileService $files;

	public function __construct(
		MediaFileService $files
	) {
		$this->files = $files;
	}

	public function generateAvatar( ?string $remoteUrl, string $externalId, ?string $username = null ): string {
		$remoteUrl = is_string( $remoteUrl ) ? trim( $remoteUrl ) : '';
		if ( $remoteUrl === '' ) {
			return '';
		}

		if ( Env::$media_dir === '' || Env::$media_url === '' ) {
			return $remoteUrl;
		}

		$paths = $this->avatarPaths( $externalId, $username, self::EXTENSION );
		$abs   = $paths['abs'];
		$url   = $paths['url'];
		if ( \is_file( $paths['abs'] ) ) {
			return $url;
		}

		try {
			$result = $this->files->saveImageFromUrl( $remoteUrl, $abs, self::WIDTH, self::EXTENSION, 'avatar', 60 );
			$paths  = $this->avatarPaths( $externalId, $username, $result['ext'] );
			$abs    = $paths['abs'];
			$url    = $paths['url'];
		} catch ( \Throwable $e ) {
			return $remoteUrl;
		}

		return $url;
	}

	private function avatarPaths( string $externalId, ?string $username, string $ext ): array {
		$baseDir = Env::$media_dir;
		$baseUrl = Env::$media_url;

		$dir     = rtrim( $baseDir, '/\\' ) . '/avatars';
		$urlBase = rtrim( $baseUrl, '/\\' ) . '/avatars';

		$fileBase = $this->avatarFileBase( $externalId, $username );
		if ( $fileBase === '' ) {
			$fileBase = 'avatar-' . (string) time();
		}

		$fileName = $fileBase . '.' . $ext;

		return [
			'dir' => $dir,
			'abs' => $dir . '/' . $fileName,
			'url' => $urlBase . '/' . $fileName,
			'ext' => $ext,
		];
	}

	private function avatarFileBase( string $externalId, ?string $username ): string {
		$username = is_string( $username ) ? trim( $username ) : '';
		if ( $username !== '' ) {
			return sanitize_file_name( 'avatar-' . $username );
		}

		return sanitize_file_name( 'avatar-' . $externalId );
	}
}
