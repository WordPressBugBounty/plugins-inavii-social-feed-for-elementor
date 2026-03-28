<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application;

final class MediaIdsCollector {
	private const IDS_PAGE_SIZE = 100;

	/**
	 * @param array    $firstPage
	 * @param int      $total
	 * @param callable $fetchPage Receives `(int $limit, int $offset): array`.
	 *
	 * @return int[]
	 */
	public function collect( array $firstPage, int $total, callable $fetchPage ): array {
		$ids = $this->extractIds( $firstPage );
		if ( $total <= count( $ids ) ) {
			return $ids;
		}

		$offset = count( $firstPage );
		while ( $offset < $total ) {
			$page = $fetchPage( self::IDS_PAGE_SIZE, $offset );
			$rows = isset( $page['media'] ) && is_array( $page['media'] ) ? $page['media'] : [];
			if ( $rows === [] ) {
				break;
			}

			$ids = array_merge( $ids, $this->extractIds( $rows ) );
			$ids = array_values( array_unique( $ids ) );

			$offset += count( $rows );
		}

		return $ids;
	}

	/**
	 * @param array $items
	 *
	 * @return int[]
	 */
	private function extractIds( array $items ): array {
		$ids = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$id = isset( $item['id'] ) ? (int) $item['id'] : 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
