<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Storage\MediaRepository;

final class MediaFileWorker {

	private const MAIN_WIDTH  = 768;
	private const THUMB_WIDTH = 320;
	private const CHILD_WIDTH = 768;

	private MediaRepository $repository;
	private MediaPaths $paths;
	private MediaFileService $files;

	public function __construct(
		MediaRepository $repository,
		MediaPaths $paths,
		MediaFileService $files
	) {
		$this->repository = $repository;
		$this->paths      = $paths;
		$this->files      = $files;
	}

	/**
	 * @param array $row
	 */
	public function processFile( array $row ): bool {
		$id        = isset( $row['id'] ) ? (int) $row['id'] : 0;
		$igMediaId = isset( $row['ig_media_id'] ) ? (string) $row['ig_media_id'] : '';
		$sourceKey = isset( $row['source_key'] ) ? (string) $row['source_key'] : '';

		if ( $id <= 0 || $igMediaId === '' || $sourceKey === '' ) {
			if ( $id > 0 ) {
				$this->repository->files()->markPostFailed( $id, 'Invalid DB row (missing id/source_key/ig_media_id)' );
			}
			return false;
		}

		try {
			$remoteUrl = $this->getUrl( $row );
			if ( $remoteUrl === '' ) {
				throw new \RuntimeException( 'Missing remote url (url/media_url)' );
			}

			$mainExt  = $this->paths->preferredExtension( 'main' );
			$thumbExt = $this->paths->preferredExtension( 'thumb' );

			[$mainAbs, $mainRel]   = $this->paths->buildMainPaths( $igMediaId, $mainExt );
			[$thumbAbs, $thumbRel] = $this->paths->buildThumbPaths( $igMediaId, $thumbExt );
			$this->paths->ensureDirectoryForFile( $mainAbs );

			if ( \file_exists( $mainAbs ) && \file_exists( $thumbAbs ) ) {
				$this->repository->files()->markPostReady( $id, $mainRel, $thumbRel );
				return true;
			}

			try {
				if ( ! \file_exists( $mainAbs ) ) {
					$width  = $this->imageWidth( 'main', self::MAIN_WIDTH );
					$result = $this->files->saveImageFromUrl( $remoteUrl, $mainAbs, $width, $mainExt, 'main', 60 );
					if ( $result['ext'] !== $mainExt ) {
						[$mainAbs, $mainRel] = $this->paths->buildMainPaths( $igMediaId, $result['ext'] );
					}
				}

				if ( ! \file_exists( $thumbAbs ) ) {
					$width  = $this->imageWidth( 'thumb', self::THUMB_WIDTH );
					$result = $this->files->saveImageFromUrl( $remoteUrl, $thumbAbs, $width, $thumbExt, 'thumb', 60 );
					if ( $result['ext'] !== $thumbExt ) {
						[$thumbAbs, $thumbRel] = $this->paths->buildThumbPaths( $igMediaId, $result['ext'] );
					}
				}
			} catch ( \Throwable $e ) {
				if ( ! \file_exists( $mainAbs ) ) {
					$this->repository->files()->markPostFailed( $id, $e->getMessage() );
					return false;
				}

				$mainRelResolved  = $this->resolveExistingRelPath( $mainAbs, $mainRel );
				$thumbRelResolved = $this->resolveExistingRelPath( $thumbAbs, $thumbRel );

				$this->repository->files()->markPostReady(
					$id,
					$mainRelResolved ?? $mainRel,
					$thumbRelResolved
				);
				return true;
			}

			$this->repository->files()->markPostReady( $id, $mainRel, $thumbRel );
			$this->updateChildren( $id, $row );
			return true;
		} catch ( \Throwable $e ) {
			$this->repository->files()->markPostFailed( $id, $e->getMessage() );
		}

		return false;
	}

	/**
	 * @param array $row
	 */
	private function getUrl( array $row ): string {
		$url = isset( $row['url'] ) ? trim( (string) $row['url'] ) : '';
		if ( $url !== '' ) {
			return $url;
		}

		return isset( $row['media_url'] ) ? trim( (string) $row['media_url'] ) : '';
	}

	/**
	 * @param array $row
	 */
	private function updateChildren( int $id, array $row ): void {
		$childrenJson = isset( $row['children_json'] ) ? (string) $row['children_json'] : '';
		if ( $childrenJson === '' ) {
			return;
		}

		$children = json_decode( $childrenJson, true );
		if ( ! is_array( $children ) || $children === [] ) {
			return;
		}

		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$childId = isset( $child['ig_media_id'] ) ? (string) $child['ig_media_id'] : '';
			if ( $childId === '' ) {
				continue;
			}

			$url = isset( $child['url'] ) ? trim( (string) $child['url'] ) : '';
			if ( $url === '' && isset( $child['thumbnail_url'] ) ) {
				$url = trim( (string) $child['thumbnail_url'] );
			}
			if ( $url === '' && isset( $child['media_url'] ) ) {
				$url = trim( (string) $child['media_url'] );
			}
			if ( $url === '' ) {
				continue;
			}

			try {
				$childExt            = $this->paths->preferredExtension( 'child' );
				[$mainAbs, $mainRel] = $this->paths->buildMainPaths( $childId, $childExt );
				$this->paths->ensureDirectoryForFile( $mainAbs );

				if ( ! \file_exists( $mainAbs ) ) {
					$width = $this->imageWidth( 'child', self::CHILD_WIDTH );
					try {
						$result = $this->files->saveImageFromUrl( $url, $mainAbs, $width, $childExt, 'child', 60 );
						if ( $result['ext'] !== $childExt ) {
							[$mainAbs, $mainRel] = $this->paths->buildMainPaths( $childId, $result['ext'] );
						}
					} catch ( \Throwable $e ) {
						continue;
					}
				}

				$this->repository->files()->children->markChildReady( $id, $childId, $mainRel );
			} catch ( \Throwable $e ) {
				$this->repository->files()->children->markChildFailed( $id, $childId, $e->getMessage() );
			}
		}

		return;
	}

	private function imageWidth( string $context, int $default ): int {
		$width = apply_filters( 'inavii/social-feed/media/image_width', $default, $context );
		if ( ! is_numeric( $width ) ) {
			return $default;
		}
		$width = (int) $width;
		return $width > 0 ? $width : $default;
	}

	private function resolveExistingRelPath( string $abs, string $rel ): ?string {
		if ( $rel === '' ) {
			return null;
		}

		if ( \file_exists( $abs ) ) {
			return $rel;
		}

		$jpgRel = preg_replace( '/\.(webp)$/i', '.jpg', $rel ) ?? $rel;
		if ( $jpgRel === $rel ) {
			return null;
		}

		$jpgAbs = preg_replace( '/\.(webp)$/i', '.jpg', $abs ) ?? $abs;
		if ( $jpgAbs !== '' && \file_exists( $jpgAbs ) ) {
			return $jpgRel;
		}

		return null;
	}
}
