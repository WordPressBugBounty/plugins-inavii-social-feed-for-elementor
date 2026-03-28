<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Source\Storage;

use Inavii\Instagram\Database\Tables\FeedSourcesTable;
use Inavii\Instagram\Database\Tables\SourcesTable;

final class FeedSourcesRepository {

	private FeedSourcesTable $table;
	private SourcesTable $sourcesTable;

	public function __construct( FeedSourcesTable $table, SourcesTable $sourcesTable ) {
		$this->table       = $table;
		$this->sourcesTable = $sourcesTable;
	}

	private function tableName(): string {
		return $this->table->table_name();
	}

	private function sourcesTableName(): string {
		return $this->sourcesTable->table_name();
	}

	public function add( int $feedId, int $sourceId ): void {
		global $wpdb;

		if ( $feedId <= 0 || $sourceId <= 0 ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"
            INSERT INTO {$table} (feed_id, source_id, created_at)
            VALUES (%d, %d, %s)
            ON DUPLICATE KEY UPDATE created_at = VALUES(created_at)
            ",
				$feedId,
				$sourceId,
				$now
			)
		);
	}

	public function remove( int $feedId, int $sourceId ): void {
		global $wpdb;

		if ( $feedId <= 0 || $sourceId <= 0 ) {
			return;
		}

		$table = $this->tableName();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table,
			[
				'feed_id'   => $feedId,
				'source_id' => $sourceId,
			],
			[ '%d','%d' ]
		);
	}

	public function removeByFeedId( int $feedId ): void {
		global $wpdb;

		if ( $feedId <= 0 ) {
			return;
		}

		$table = $this->tableName();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, [ 'feed_id' => $feedId ], [ '%d' ] );
	}

	public function removeBySourceId( int $sourceId ): void {
		global $wpdb;

		if ( $sourceId <= 0 ) {
			return;
		}

		$table = $this->tableName();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, [ 'source_id' => $sourceId ], [ '%d' ] );
	}

	public function countBySourceId( int $sourceId ): int {
		global $wpdb;

		if ( $sourceId <= 0 ) {
			return 0;
		}

		$table = $this->tableName();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$cnt = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM {$table} WHERE source_id = %d",
				$sourceId
			)
		);

		return (int) $cnt;
	}

	/**
	 * @return int[]
	 */
	public function getSourceIdsInUse(): array {
		global $wpdb;

		$table = $this->tableName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col( "SELECT DISTINCT source_id FROM {$table}" );

		if ( ! is_array( $ids ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'intval', $ids ) ) );
	}

	/**
	 * @return int[]
	 */
	public function getSourceIdsByFeedId( int $feedId ): array {
		global $wpdb;

		if ( $feedId <= 0 ) {
			return [];
		}

		$table = $this->tableName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT source_id FROM {$table} WHERE feed_id = %d",
				$feedId
			)
		);

		if ( ! is_array( $ids ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'intval', $ids ) ) );
	}

	/**
	 * @return int[]
	 */
	public function getFeedIdsBySourceId( int $sourceId ): array {
		global $wpdb;

		if ( $sourceId <= 0 ) {
			return [];
		}

		$table = $this->tableName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT feed_id FROM {$table} WHERE source_id = %d",
				$sourceId
			)
		);

		if ( ! is_array( $ids ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'intval', $ids ) ) );
	}

	/**
	 * @return string[]
	 */
	public function getSourceKeysByFeedId( int $feedId ): array {
		global $wpdb;

		if ( $feedId <= 0 ) {
			return [];
		}

		$feedSourcesTable = $this->tableName();
		$sourcesTable     = $this->sourcesTableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT s.source_key
				FROM {$feedSourcesTable} fs
				INNER JOIN {$sourcesTable} s ON s.id = fs.source_id
				WHERE fs.feed_id = %d
				",
				$feedId
			)
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$keys = [];
		foreach ( $rows as $key ) {
			$key = trim( (string) $key );
			if ( $key !== '' ) {
				$keys[] = $key;
			}
		}

		return array_values( array_unique( $keys ) );
	}
}
