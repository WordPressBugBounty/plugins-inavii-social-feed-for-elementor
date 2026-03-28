<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Storage;

use Inavii\Instagram\Database\Tables\MediaTable;
use Inavii\Instagram\Logger\Logger;

final class MediaFilesRepository {

	private const STATUS_NONE        = 'none';
	private const STATUS_QUEUED      = 'queued';
	private const STATUS_DOWNLOADING = 'downloading';
	private const STATUS_READY       = 'ready';
	private const STATUS_FAILED      = 'failed';
	private const STATUS_DEAD        = 'dead';
	private const MAX_ATTEMPTS       = 5;

	private MediaTable $mediaTable;
	public MediaChildrenRepository $children;

	public function __construct( MediaTable $mediaTable, MediaChildrenRepository $children ) {
		$this->mediaTable = $mediaTable;
		$this->children   = $children;
	}

	private function tableName(): string {
		return $this->mediaTable->table_name();
	}

	private function ensureTable(): bool {
		return $this->mediaTable->ensureExists();
	}

	public function children(): MediaChildrenRepository {
		return $this->children;
	}

	public function queueForSource( string $sourceKey, int $limit = 30 ): int {
		global $wpdb;

		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		if ( $sourceKey === '' ) {
			return 0;
		}
		if ( ! $this->ensureTable() ) {
			return 0;
		}

		$limit = $this->clampLimit( $limit );
		$table = $this->tableName();
		$now   = $this->now();

		$sql = "
            UPDATE {$table}
            SET
                file_status = '" . self::STATUS_QUEUED . "',
                file_error = NULL,
                file_updated_at = %s
            WHERE id IN (
                SELECT id FROM (
                    SELECT id
                    FROM {$table}
                    WHERE source_key = %s
                      AND (file_status IS NULL OR file_status IN ('" . self::STATUS_NONE . "','" . self::STATUS_FAILED . "'))
                      AND (file_attempts IS NULL OR file_attempts < %d)
                      AND COALESCE(url, '') <> ''
                      AND NOT (
                        (COALESCE(video_url, '') <> '' AND COALESCE(url, '') = COALESCE(video_url, ''))
                        OR LOWER(COALESCE(url, '')) LIKE '%.mp4%'
                        OR LOWER(COALESCE(url, '')) LIKE '%.m3u8%'
                      )
                    ORDER BY posted_at DESC, id DESC
                    LIMIT %d
                ) AS t
            )
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $sql, $now, $sourceKey, self::MAX_ATTEMPTS, $limit ) );

		if ( $result === false ) {
			$this->logDbError( 'enqueue', $wpdb->last_error );
			return 0;
		}

		return (int) $result;
	}

	/**
	 * @return array
	 */
	public function getQueued( int $batchSize = 20 ): array {
		global $wpdb;

		$batchSize = $this->clampBatch( $batchSize );
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$this->resetQueuedWithoutUrl();

		$table = $this->tableName();
		$now   = $this->now();

		$selectSql = "
            SELECT id
            FROM {$table}
            WHERE file_status = '" . self::STATUS_QUEUED . "'
              AND COALESCE(url, '') <> ''
              AND (file_attempts IS NULL OR file_attempts < %d)
            ORDER BY file_updated_at ASC, id ASC
            LIMIT %d
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( $selectSql, self::MAX_ATTEMPTS, $batchSize ) );

		if ( ! is_array( $ids ) || $ids === [] ) {
			return [];
		}

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( $ids === [] ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$updateSql = "
            UPDATE {$table}
            SET file_status = '" . self::STATUS_DOWNLOADING . "',
                file_error = NULL,
                file_updated_at = %s
            WHERE file_status = '" . self::STATUS_QUEUED . "'
              AND (file_attempts IS NULL OR file_attempts < %d)
              AND id IN ({$placeholders})
        ";

		$params = array_merge( [ $now, self::MAX_ATTEMPTS ], $ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$updated = $wpdb->query( $wpdb->prepare( $updateSql, $params ) );

		if ( $updated === false ) {
			$this->logDbError( 'claim', $wpdb->last_error );
			return [];
		}

		// media_url usunięte, bierzemy tylko url
		$fetchSql = "
            SELECT id, source_key, ig_media_id, url, children_json
            FROM {$table}
            WHERE id IN ({$placeholders})
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $fetchSql, $ids ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	public function markPostReady( int $id, string $filePath, ?string $fileThumbPath ): void {
		global $wpdb;

		if ( $id <= 0 ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		$table = $this->tableName();
		$now   = $this->now();

		$data = [
			'file_status'     => self::STATUS_READY,
			'file_error'      => null,
			'file_path'       => $filePath,
			'file_thumb_path' => $fileThumbPath,
			'file_attempts'   => 0,
			'file_updated_at' => $now,
		];

		$format = [
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update( $table, $data, [ 'id' => $id ], $format, [ '%d' ] );

		if ( $result === false ) {
			$this->logDbError( 'mark_ready', $wpdb->last_error );
			return;
		}
	}

	public function markPostFailed( int $id, string $error ): void {
		global $wpdb;

		if ( $id <= 0 ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		$table = $this->tableName();
		$now   = $this->now();

		$error = $this->truncateError( $error );

		$sql = "
            UPDATE {$table}
            SET
                file_attempts = COALESCE(file_attempts, 0) + 1,
                file_status = CASE
                    WHEN (COALESCE(file_attempts, 0) + 1) >= %d THEN '" . self::STATUS_DEAD . "'
                    ELSE '" . self::STATUS_FAILED . "'
                END,
                file_error = %s,
                file_updated_at = %s
            WHERE id = %d
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $sql, self::MAX_ATTEMPTS, $error, $now, $id ) );

		if ( $result === false ) {
			$this->logDbError( 'mark_failed', $wpdb->last_error );
			return;
		}
	}

	/**
	 * Recover stuck downloads by re-queueing items that have been in
	 * "downloading" state for too long and are below the retry limit.
	 */
	public function recoverStuckDownloads( int $staleMinutes = 30, int $limit = 200 ): int {
		global $wpdb;

		$staleMinutes = max( 5, $staleMinutes );
		$limit        = $this->clampLimit( $limit );
		if ( ! $this->ensureTable() ) {
			return 0;
		}
		$table = $this->tableName();
		$now   = $this->now();

		$sql = "
            UPDATE {$table}
            SET
                file_attempts = COALESCE(file_attempts, 0) + 1,
                file_status = CASE
                    WHEN (COALESCE(file_attempts, 0) + 1) >= %d THEN '" . self::STATUS_DEAD . "'
                    ELSE '" . self::STATUS_QUEUED . "'
                END,
                file_error = CASE
                    WHEN (COALESCE(file_attempts, 0) + 1) >= %d THEN 'Stuck download, max retries reached'
                    ELSE NULL
                END,
                file_updated_at = %s
            WHERE id IN (
                SELECT id FROM (
                    SELECT id
                    FROM {$table}
                    WHERE file_status = '" . self::STATUS_DOWNLOADING . "'
                      AND file_updated_at IS NOT NULL
                      AND file_updated_at < (UTC_TIMESTAMP() - INTERVAL %d MINUTE)
                      AND (file_attempts IS NULL OR file_attempts < %d)
                    ORDER BY file_updated_at ASC, id ASC
                    LIMIT %d
                ) AS t
            )
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				self::MAX_ATTEMPTS,
				self::MAX_ATTEMPTS,
				$now,
				$staleMinutes,
				self::MAX_ATTEMPTS,
				$limit
			)
		);

		if ( $result === false ) {
			$this->logDbError( 'recover', $wpdb->last_error );
			return 0;
		}

		return (int) $result;
	}


	public function countQueued(): int {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return 0;
		}

		$this->resetQueuedWithoutUrl();

		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$cnt = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM {$table} WHERE file_status = %s AND COALESCE(url, '') <> ''",
				self::STATUS_QUEUED
			)
		);

		return (int) $cnt;
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

	private function clampLimit( int $limit ): int {
		if ( $limit < 1 ) {
			return 1;
		}
		if ( $limit > 500 ) {
			return 500;
		}
		return $limit;
	}

	private function resetQueuedWithoutUrl(): void {
		global $wpdb;

		$table = $this->tableName();
		$now   = $this->now();

		$sql = "
			UPDATE {$table}
			SET file_status = '" . self::STATUS_NONE . "',
			    file_error = NULL,
			    file_updated_at = %s
			WHERE file_status = '" . self::STATUS_QUEUED . "'
			  AND COALESCE(url, '') = ''
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $sql, $now ) );

		if ( $result === false ) {
			$this->logDbError( 'reset_queued_without_url', $wpdb->last_error );
		}
	}

	private function normalizeSourceKey( string $sourceKey ): string {
		return trim( $sourceKey );
	}

	private function now(): string {
		return current_time( 'mysql', true );
	}

	private function clampBatch( int $batch ): int {
		if ( $batch < 1 ) {
			return 1;
		}
		if ( $batch > 100 ) {
			return 100;
		}
		return $batch;
	}

	private function logDbError( string $action, string $error ): void {
		if ( $error === '' ) {
			return;
		}

		Logger::error(
			'db/media_files',
			'Media files DB ' . $action . ' failed.',
			[
				'error' => $error,
			]
		);
	}
}
