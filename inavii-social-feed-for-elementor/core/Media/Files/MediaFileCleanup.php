<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

use Inavii\Instagram\Media\Storage\MediaRepository;

final class MediaFileCleanup {

	private MediaRepository $repository;
	private MediaFileRemover $files;

	public function __construct( MediaRepository $repository, MediaFileRemover $files ) {
		$this->repository = $repository;
		$this->files      = $files;
	}

	/**
	 * @param array $rows
	 * @return int[] parent ids
	 */
	public function deletePostFilesWithChildren( array $rows ): array {
		if ( $rows === [] ) {
			return [];
		}

		$parentIds = $this->collectIds( $rows );
		$paths     = $this->collectPaths( $rows );
		if ( $paths !== [] ) {
			$this->files->deletePaths( $paths );
		}

		if ( $parentIds !== [] ) {
			$childRows  = $this->repository->files()->children->getByParentIds( $parentIds );
			$childPaths = $this->collectPaths( $childRows );
			if ( $childPaths !== [] ) {
				$this->files->deletePaths( $childPaths );
			}
		}

		return $parentIds;
	}

	/**
	 * @param array $rows
	 * @return int[]
	 */
	private function collectIds( array $rows ): array {
		$ids = [];
		foreach ( $rows as $row ) {
			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * @param array $rows
	 * @return string[]
	 */
	private function collectPaths( array $rows ): array {
		$paths = [];
		foreach ( $rows as $row ) {
			$path = isset( $row['file_path'] ) ? (string) $row['file_path'] : '';
			if ( $path !== '' ) {
				$paths[] = $path;
			}

			$thumb = isset( $row['file_thumb_path'] ) ? (string) $row['file_thumb_path'] : '';
			if ( $thumb !== '' ) {
				$paths[] = $thumb;
			}
		}

		return $paths;
	}
}
