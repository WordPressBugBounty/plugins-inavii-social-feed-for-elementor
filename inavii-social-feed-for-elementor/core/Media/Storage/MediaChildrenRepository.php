<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Storage;

use Inavii\Instagram\Database\Tables\MediaChildrenTable;
use Inavii\Instagram\Logger\Logger;

final class MediaChildrenRepository {

	private const STATUS_NONE   = 'none';
	private const STATUS_READY  = 'ready';
	private const STATUS_FAILED = 'failed';
	private const STATUS_DEAD   = 'dead';
	private const MAX_ATTEMPTS  = 5;

	private MediaChildrenTable $table;

	public function __construct( MediaChildrenTable $table ) {
		$this->table = $table;
	}

	private function tableName(): string {
		return $this->table->table_name();
	}

	private function ensureTable(): bool {
		return $this->table->ensureExists();
	}

	/**
	 * @param int[] $parentIds
	 * @return array
	 */
	public function getByParentIds( array $parentIds ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $parentIds ) ) );
		if ( $ids === [] ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = $this->tableName();

		$sql = "
            SELECT parent_id, ig_media_id, file_path, file_status
            FROM {$table}
            WHERE parent_id IN ({$placeholders})
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	public function deleteByParentIds( array $parentIds ): void {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $parentIds ) ) );
		if ( $ids === [] ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = $this->tableName();

		$sql = "DELETE FROM {$table} WHERE parent_id IN ({$placeholders})";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $sql, ...$ids ) );
	}

	public function markChildReady( int $parentId, string $igMediaId, string $filePath ): void {
		global $wpdb;

		if ( $parentId <= 0 || $igMediaId === '' ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		$table = $this->tableName();
		$now   = $this->now();

		$sql = "
            INSERT INTO {$table} (
                parent_id, ig_media_id, file_path, file_status,
                file_error, file_attempts, file_updated_at, created_at, updated_at
            ) VALUES (
                %d, %s, %s, %s,
                NULL, 0, %s, %s, %s
            )
            ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                file_status = VALUES(file_status),
                file_error = NULL,
                file_attempts = 0,
                file_updated_at = VALUES(file_updated_at),
                updated_at = VALUES(updated_at)
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$parentId,
				$igMediaId,
				$filePath,
				self::STATUS_READY,
				$now,
				$now,
				$now
			)
		);

		if ( $result === false ) {
			$this->logDbError( 'mark_ready', $wpdb->last_error );
			return;
		}
	}

	public function markChildFailed( int $parentId, string $igMediaId, string $error ): void {
		global $wpdb;

		if ( $parentId <= 0 || $igMediaId === '' ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		$table = $this->tableName();
		$now   = $this->now();
		$error = $this->truncateError( $error );

		$sql = "
            INSERT INTO {$table} (
                parent_id, ig_media_id, file_status, file_error,
                file_attempts, file_updated_at, created_at, updated_at
            ) VALUES (
                %d, %s, %s, %s,
                1, %s, %s, %s
            )
            ON DUPLICATE KEY UPDATE
                file_attempts = file_attempts + 1,
                file_status = CASE
                    WHEN file_attempts + 1 >= %d THEN %s
                    ELSE %s
                END,
                file_error = VALUES(file_error),
                file_updated_at = VALUES(file_updated_at),
                updated_at = VALUES(updated_at)
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$parentId,
				$igMediaId,
				self::STATUS_FAILED,
				$error,
				$now,
				$now,
				$now,
				self::MAX_ATTEMPTS,
				self::STATUS_DEAD,
				self::STATUS_FAILED
			)
		);

		if ( $result === false ) {
			$this->logDbError( 'mark_failed', $wpdb->last_error );
			return;
		}
	}

	private function now(): string {
		return current_time( 'mysql', true );
	}

	private function truncateError( string $error ): string {
		$error = trim( $error );
		if ( $error === '' ) {
			return 'Unknown error';
		}

		if ( function_exists( 'mb_substr' ) ) {
			return (string) mb_substr( $error, 0, 1000 );
		}

		return substr( $error, 0, 1000 );
	}

	private function logDbError( string $action, string $error ): void {
		if ( $error === '' ) {
			return;
		}

		Logger::error(
			'db/media_children',
			'Media children DB ' . $action . ' failed.',
			[
				'error' => $error,
			]
		);
	}
}
