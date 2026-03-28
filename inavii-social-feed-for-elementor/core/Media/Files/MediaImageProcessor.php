<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

final class MediaImageProcessor {

	private MediaDownloader $downloader;
	private ImageGenerator $images;
	private MediaFileRemover $files;

	public function __construct(
		MediaDownloader $downloader,
		ImageGenerator $images,
		MediaFileRemover $files
	) {
		$this->downloader = $downloader;
		$this->images     = $images;
		$this->files      = $files;
	}

	/**
	 * Download image from URL and generate a resized file.
	 *
	 * @return array{path:string,ext:string}
	 */
	public function saveFromUrl(
		string $url,
		string $targetPath,
		int $width,
		string $preferredExt,
		string $context = 'main',
		int $timeout = 60
	): array {
		$targetPath = trim( $targetPath );
		if ( $targetPath === '' ) {
			throw new \InvalidArgumentException( 'Target path cannot be empty' );
		}

		$this->ensureDirForFile( $targetPath );

		$tmp = $this->downloader->downloadToTemp( $url, $timeout );
		if ( \is_wp_error( $tmp ) ) {
			throw new \RuntimeException( 'Download error: ' . $tmp->get_error_message() );
		}

		$tmpPath = (string) $tmp;

		try {
			$extUsed   = $this->images->generate( $tmpPath, $targetPath, $width, $preferredExt, $context );
			$finalPath = $this->replaceExtension( $targetPath, $extUsed );
			return [
				'path' => $finalPath,
				'ext'  => $extUsed,
			];
		} finally {
			$this->files->deletePath( $tmpPath );
		}
	}

	private function ensureDirForFile( string $filePath ): void {
		$dir = \dirname( $filePath );
		if ( \is_dir( $dir ) ) {
			return;
		}

		if ( ! \function_exists( 'wp_mkdir_p' ) ) {
			require_once ABSPATH . 'wp-includes/functions.php';
		}

		\wp_mkdir_p( $dir );
	}

	private function replaceExtension( string $path, string $ext ): string {
		return preg_replace( '/\.[a-z0-9]+$/i', '.' . $ext, $path ) ?? $path;
	}
}
