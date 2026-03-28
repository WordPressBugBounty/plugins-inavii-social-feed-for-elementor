<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application;

use Inavii\Instagram\Media\Files\MediaFileCleanup;
use Inavii\Instagram\Media\Files\MediaFileRemover;
use Inavii\Instagram\Media\Files\MediaImageProcessor;

final class MediaFileService {

	private MediaFileCleanup $cleanup;
	private MediaFileRemover $remover;
	private MediaImageProcessor $images;

	public function __construct(
		MediaFileCleanup $cleanup,
		MediaFileRemover $remover,
		MediaImageProcessor $images
	) {
		$this->cleanup = $cleanup;
		$this->remover = $remover;
		$this->images  = $images;
	}

	public function deleteFromUrl( string $url ): void {
		$this->remover->deleteFromUrl( $url );
	}

	public function deletePath( string $path ): void {
		$this->remover->deletePath( $path );
	}

	/**
	 * @param string[] $paths
	 */
	public function deletePaths( array $paths ): void {
		$this->remover->deletePaths( $paths );
	}

	public function deleteMediaDirectory(): void {
		$this->remover->deleteMediaDirectory();
	}

	/**
	 * @param array $rows
	 * @return int[] parent ids
	 */
	public function deletePostFilesWithChildren( array $rows ): array {
		return $this->cleanup->deletePostFilesWithChildren( $rows );
	}

	/**
	 * Download image from URL and generate a resized file.
	 *
	 * @return array{path:string,ext:string}
	 */
	public function saveImageFromUrl(
		string $url,
		string $targetPath,
		int $width,
		string $preferredExt,
		string $context = 'main',
		int $timeout = 60
	): array {
		return $this->images->saveFromUrl( $url, $targetPath, $width, $preferredExt, $context, $timeout );
	}
}
