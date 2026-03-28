<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application\Mapper;

use Inavii\Instagram\Media\Storage\MediaRepository;

final class MediaChildrenMapper {

	private MediaRepository $repository;

	public function __construct( MediaRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * @param array $items
	 * @return array>
	 */
	public function mapByParents( array $items ): array {
		$parentIds = $this->extractParentIds( $items );

		if ( $parentIds === [] ) {
			return [];
		}

		$rows = $this->repository->files()->children->getByParentIds( $parentIds );
		if ( $rows === [] ) {
			return [];
		}

		return $this->buildMap( $rows );
	}

	/**
	 * @param array $items
	 * @return int[]
	 */
	private function extractParentIds( array $items ): array {
		$parentIds = [];
		foreach ( $items as $row ) {
			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $id > 0 ) {
				$parentIds[] = $id;
			}
		}

		return $parentIds;
	}

	/**
	 * @param array $rows
	 * @return array>
	 */
	private function buildMap( array $rows ): array {
		$map = [];
		foreach ( $rows as $row ) {
			$parentId = isset( $row['parent_id'] ) ? (int) $row['parent_id'] : 0;
			$childId  = isset( $row['ig_media_id'] ) ? (string) $row['ig_media_id'] : '';
			if ( $parentId <= 0 || $childId === '' ) {
				continue;
			}

			$map[ $parentId ][ $childId ] = [
				'file_path' => $row['file_path'] ?? '',
			];
		}

		return $map;
	}
}
