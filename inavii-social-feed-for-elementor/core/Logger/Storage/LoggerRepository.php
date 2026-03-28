<?php
declare(strict_types=1);

namespace Inavii\Instagram\Logger\Storage;

use Inavii\Instagram\Database\Tables\LogsTable;
use Inavii\Instagram\Database\Tables\MediaChildrenTable;
use Inavii\Instagram\Database\Tables\MediaTable;
use Inavii\Instagram\Database\Tables\SourcesTable;

final class LoggerRepository {

	private LogsTable $table;
	private SourcesTable $sourcesTable;
	private MediaTable $mediaTable;
	private MediaChildrenTable $mediaChildrenTable;

	public function __construct(
		LogsTable $table,
		SourcesTable $sourcesTable,
		MediaTable $mediaTable,
		MediaChildrenTable $mediaChildrenTable
	) {
		$this->table              = $table;
		$this->sourcesTable       = $sourcesTable;
		$this->mediaTable         = $mediaTable;
		$this->mediaChildrenTable = $mediaChildrenTable;
	}

	public function insert(
		string $level,
		string $component,
		string $message,
		?string $contextJson,
		string $createdAt
	): bool {
		if ( ! $this->table->ensureExists() ) {
			return false;
		}

		global $wpdb;
		$table = $this->table->table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table,
			[
				'level'        => $level,
				'component'    => $component,
				'message'      => $message,
				'context_json' => $contextJson,
				'created_at'   => $createdAt,
			],
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		return $result !== false;
	}

	/**
	 * @return array
	 */
	public function latest( int $limit ): array {
		if ( ! $this->table->ensureExists() ) {
			return [];
		}

		global $wpdb;
		$table = $this->table->table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, level, component, message, context_json, created_at
                 FROM {$table}
                 ORDER BY id DESC
                 LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	public function clear(): void {
		if ( ! $this->table->ensureExists() ) {
			return;
		}

		global $wpdb;
		$table = $this->table->table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}

	public function trimTo( int $limit ): void {
		if ( $limit <= 0 || ! $this->table->ensureExists() ) {
			return;
		}

		global $wpdb;
		$table  = $this->table->table_name();
		$offset = $limit - 1;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$threshold = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET %d",
				$offset
			)
		);

		if ( ! is_numeric( $threshold ) ) {
			return;
		}

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id < %d",
				(int) $threshold
			)
		);
	}

	/**
	 * @return array{total:int,active:int,disabled:int,pinned:int,due:int}
	 */
	public function sourcesStats(): array {
		$stats = [
			'total'    => 0,
			'active'   => 0,
			'disabled' => 0,
			'pinned'   => 0,
			'due'      => 0,
		];

		if ( ! $this->sourcesTable->ensureExists() ) {
			return $stats;
		}

		global $wpdb;
		$name = $this->sourcesTable->table_name();
		$now  = current_time( 'mysql' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['active'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$name} WHERE status = %s", 'active' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['disabled'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$name} WHERE status = %s", 'disabled' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['pinned'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name} WHERE is_pinned = 1" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['due'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$name} WHERE status = %s AND (next_sync_at IS NULL OR next_sync_at <= %s)",
				'active',
				$now
			)
		);

		return $stats;
	}

	/**
	 * @return array{total:int,queued:int,ready:int,failed:int,children:int}
	 */
	public function mediaStats(): array {
		$stats = [
			'total'    => 0,
			'queued'   => 0,
			'ready'    => 0,
			'failed'   => 0,
			'children' => 0,
		];

		if ( ! $this->mediaTable->ensureExists() ) {
			return $stats;
		}

		global $wpdb;
		$name = $this->mediaTable->table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['queued'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$name} WHERE file_status = %s", 'queued' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['ready'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$name} WHERE file_status = %s", 'ready' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['failed'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$name} WHERE file_status = %s", 'failed' ) );

		if ( $this->mediaChildrenTable->ensureExists() ) {
			$childName = $this->mediaChildrenTable->table_name();
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$stats['children'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$childName}" );
		}

		return $stats;
	}
}
