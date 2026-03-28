<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Storage;

use Inavii\Instagram\Database\Tables\SourcesTable;
use Inavii\Instagram\Media\Source\Domain\Source;

final class SourcesRepository {
	private const STATUS_ACTIVE   = 'active';
	private const STATUS_DISABLED = 'disabled';
	private const STATUS_ERROR    = 'error';

	private SourcesTable $table;

	public function __construct( SourcesTable $table ) {
		$this->table = $table;
	}

	private function tableName(): string {
		return $this->table->table_name();
	}

	public function save(
		string $kind,
		string $sourceKey,
		?int $accountId,
		string $fetchKey
	): int {
		global $wpdb;

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		$sql = "
            INSERT INTO {$table} (
                kind, source_key, account_id, fetch_key, status,
                created_at, updated_at
            ) VALUES (
                %s, %s, %d, %s, %s,
                %s, %s
            )
            ON DUPLICATE KEY UPDATE
                kind = VALUES(kind),
                account_id = VALUES(account_id),
                fetch_key = VALUES(fetch_key),
                status = VALUES(status),
                updated_at = VALUES(updated_at)
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$kind,
				$sourceKey,
				$accountId ?? 0,
				$fetchKey,
				self::STATUS_ACTIVE,
				$now,
				$now
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE source_key = %s", $sourceKey ) );

		return (int) $id;
	}

	public function clearFailureByKey( string $sourceKey ): void {
		global $wpdb;

		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			[
				'last_error'    => null,
				'sync_attempts' => 0,
				'next_sync_at'  => null,
				'status'        => self::STATUS_ACTIVE,
				'updated_at'    => $now,
			],
			[ 'source_key' => $sourceKey ],
			[ '%s', '%d', '%s', '%s', '%s' ],
			[ '%s' ]
		);
	}

	public function clearFailuresByAccountId( int $accountId ): void {
		global $wpdb;

		if ( $accountId <= 0 ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			[
				'last_error'    => null,
				'sync_attempts' => 0,
				'next_sync_at'  => null,
				'status'        => self::STATUS_ACTIVE,
				'updated_at'    => $now,
			],
			[ 'account_id' => $accountId ],
			[ '%s', '%d', '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * @return array
	 */
	public function getToSync( int $limit = 50 ): array {
		global $wpdb;

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		// Backoff behavior:
		// - markSyncFailure() sets next_sync_at to "now + X minutes"
		// - getToSync() only returns sources where next_sync_at is NULL or already due
		// This prevents retry storms and spaces retries based on failures.
		$sql = "
            SELECT *
            FROM {$table}
            WHERE status IN (%s, %s)
              AND (next_sync_at IS NULL OR next_sync_at <= %s)
            ORDER BY next_sync_at ASC, id ASC
            LIMIT %d
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				self::STATUS_ACTIVE,
				self::STATUS_ERROR,
				$now,
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @return array
	 */
	public function getActiveWithLastSuccess(): array {
		global $wpdb;

		$table = $this->tableName();

		$sql = "
            SELECT *
            FROM {$table}
            WHERE status = %s
              AND last_success_at IS NOT NULL
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, self::STATUS_ACTIVE ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @return array|null
	 */
	public function findAccountSource( int $accountId ): ?array {
		$rows = $this->getAccountSourcesByAccountIds( [ $accountId ] );
		return $rows[ $accountId ] ?? null;
	}

	/**
	 * @param int[] $accountIds
	 * @return array
	 */
	public function getAccountSourcesByAccountIds( array $accountIds ): array {
		global $wpdb;

		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $accountIds ),
					static function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);

		if ( $ids === [] ) {
			return [];
		}

		$table        = $this->tableName();
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$args         = array_merge( [ Source::KIND_ACCOUNT ], $ids );

		$sql = "SELECT * FROM {$table} WHERE kind = %s AND account_id IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$map = [];
		foreach ( $rows as $row ) {
			$accountId = isset( $row['account_id'] ) ? (int) $row['account_id'] : 0;
			if ( $accountId > 0 ) {
				$map[ $accountId ] = $row;
			}
		}

		return $map;
	}

	/**
	 * @param int[] $accountIds
	 * @return array
	 */
	public function getSourcesByAccountIds( array $accountIds ): array {
		global $wpdb;

		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $accountIds ),
					static function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);

		if ( $ids === [] ) {
			return [];
		}

		$table        = $this->tableName();
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		$sql = "SELECT * FROM {$table} WHERE account_id IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @return array
	 */
	public function getDisabledAccountSources(): array {
		global $wpdb;

		$table = $this->tableName();

		$sql = "SELECT * FROM {$table} WHERE kind = %s AND status = %s";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, Source::KIND_ACCOUNT, self::STATUS_DISABLED ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	public function hasAnySources(): bool {
		global $wpdb;

		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$value = $wpdb->get_var( "SELECT 1 FROM {$table} LIMIT 1" );

		return $value !== null;
	}

	public function countStaleActiveSources( int $staleAfterSeconds ): int {
		global $wpdb;

		if ( $staleAfterSeconds <= 0 ) {
			$staleAfterSeconds = 3 * 24 * 60 * 60;
		}

		$table  = $this->tableName();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $staleAfterSeconds );

		$sql = "
            SELECT COUNT(*)
            FROM {$table}
            WHERE status = %s
              AND (
                    (last_success_at IS NOT NULL AND last_success_at < %s)
                 OR (last_success_at IS NULL AND created_at < %s)
              )
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$value = $wpdb->get_var(
			$wpdb->prepare(
				$sql,
				self::STATUS_ACTIVE,
				$cutoff,
				$cutoff
			)
		);

		return (int) $value;
	}

	public function markSyncSuccess( int $id ): void {
		global $wpdb;

		if ( $id <= 0 ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			[
				'last_sync_at'    => $now,
				'last_success_at' => $now,
				'last_error'      => null,
				'sync_attempts'   => 0,
				'next_sync_at'    => null,
				'status'          => self::STATUS_ACTIVE,
				'updated_at'      => $now,
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%s', '%d', '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	public function markSyncSuccessByKey( string $sourceKey ): void {
		global $wpdb;

		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			[
				'last_sync_at'    => $now,
				'last_success_at' => $now,
				'last_error'      => null,
				'sync_attempts'   => 0,
				'next_sync_at'    => null,
				'status'          => self::STATUS_ACTIVE,
				'updated_at'      => $now,
			],
			[ 'source_key' => $sourceKey ],
			[ '%s', '%s', '%s', '%d', '%s', '%s', '%s' ],
			[ '%s' ]
		);
	}

	public function markSyncFailure( int $id, string $error, int $nextMinutes = 15 ): void {
		global $wpdb;

		if ( $id <= 0 ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );
		$next  = gmdate( 'Y-m-d H:i:s', time() + ( $nextMinutes * 60 ) );

		// Backoff: after a failure, push next_sync_at to the future so we retry later.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"
            UPDATE {$table}
            SET
                last_sync_at = %s,
                last_error = %s,
                sync_attempts = sync_attempts + 1,
                next_sync_at = %s,
                status = %s,
                updated_at = %s
            WHERE id = %d
            ",
				$now,
				$error,
				$next,
				self::STATUS_ERROR,
				$now,
				$id
			)
		);
	}

	public function markAuthFailure( int $id, string $error ): void {
		global $wpdb;

		if ( $id <= 0 ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		// Auth/token failure is treated as a hard stop until the user reconnects.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"
            UPDATE {$table}
            SET
                last_sync_at = %s,
                last_error = %s,
                sync_attempts = sync_attempts + 1,
                next_sync_at = NULL,
                status = %s,
                updated_at = %s
            WHERE id = %d
            ",
				$now,
				$error,
				self::STATUS_DISABLED,
				$now,
				$id
			)
		);
	}

	public function disable( int $id ): void {
		global $wpdb;

		if ( $id <= 0 ) {
			return;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			[
				'status'     => self::STATUS_DISABLED,
				'updated_at' => $now,
			],
			[ 'id' => $id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	public function addPinnedByKey( string $sourceKey ): void {
		$this->updatePinnedByKey( $sourceKey, true );
	}

	public function removePinnedByKey( string $sourceKey ): void {
		$this->updatePinnedByKey( $sourceKey, false );
	}

	private function updatePinnedByKey( string $sourceKey, bool $isPinned ): void {
		global $wpdb;

		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return;
		}

		$table  = $this->tableName();
		$now    = current_time( 'mysql', true );
		$pinned = $isPinned ? 1 : 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			[
				'is_pinned'  => $pinned,
				'updated_at' => $now,
			],
			[ 'source_key' => $sourceKey ],
			[ '%d', '%s' ],
			[ '%s' ]
		);
	}

	/**
	 * @return array|null
	 */
	public function getByKey( string $sourceKey ): ?array {
		global $wpdb;

		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return null;
		}

		$table = $this->tableName();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE source_key = %s", $sourceKey ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param string[] $sourceKeys
	 *
	 * @return array<string,array>
	 */
	public function getByKeys( array $sourceKeys ): array {
		global $wpdb;

		$keys = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $value ): string {
							return trim( (string) $value );
						},
						$sourceKeys
					),
					static function ( string $value ): bool {
						return $value !== '';
					}
				)
			)
		);

		if ( $keys === [] ) {
			return [];
		}

		$table        = $this->tableName();
		$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
		$sql          = "SELECT * FROM {$table} WHERE source_key IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$keys ), ARRAY_A );
		if ( ! is_array( $rows ) || $rows === [] ) {
			return [];
		}

		$map = [];
		foreach ( $rows as $row ) {
			$key = isset( $row['source_key'] ) ? trim( (string) $row['source_key'] ) : '';
			if ( $key === '' ) {
				continue;
			}

			$map[ $key ] = $row;
		}

		return $map;
	}

	public function getStatusByKey( string $sourceKey ): ?string {
		global $wpdb;

		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return null;
		}

		$table = $this->tableName();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$status = $wpdb->get_var(
			$wpdb->prepare( "SELECT status FROM {$table} WHERE source_key = %s LIMIT 1", $sourceKey )
		);

		return is_string( $status ) ? $status : null;
	}

	/**
	 * @param string[] $sourceKeys
	 * @return array
	 */
	public function getLastSyncAtBySourceKeys( array $sourceKeys ): array {
		global $wpdb;

		$keys = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $value ): string {
							return trim( (string) $value );
						},
						$sourceKeys
					),
					static function ( string $key ): bool {
						return $key !== '';
					}
				)
			)
		);

		if ( $keys === [] ) {
			return [];
		}

		$table        = $this->tableName();
		$placeholders = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );
		$sql          = "SELECT source_key, last_sync_at FROM {$table} WHERE source_key IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $keys ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$map = [];
		foreach ( $rows as $row ) {
			$key = isset( $row['source_key'] ) ? trim( (string) $row['source_key'] ) : '';
			if ( $key === '' ) {
				continue;
			}

			$map[ $key ] = isset( $row['last_sync_at'] ) ? (string) $row['last_sync_at'] : '';
		}

		return $map;
	}

	public function isDisabledByKey( string $sourceKey ): bool {
		return $this->getStatusByKey( $sourceKey ) === self::STATUS_DISABLED;
	}

	/**
	 * @param int[] $sourceIds
	 *
	 * @return array
	 */
	public function getByIds( array $sourceIds ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $sourceIds ) ) );
		if ( $ids === [] ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = $this->tableName();

		$sql = "SELECT * FROM {$table} WHERE id IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$ids ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param int[] $sourceIds
	 *
	 * @return array
	 */
	public function getUnpinnedNotInIds( array $sourceIds ): array {
		global $wpdb;

		$table = $this->tableName();
		$ids   = array_values( array_filter( array_map( 'intval', $sourceIds ) ) );

		if ( $ids === [] ) {
			$sql = "SELECT * FROM {$table} WHERE is_pinned = 0";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $sql, ARRAY_A );

			return is_array( $rows ) ? $rows : [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "
            SELECT *
            FROM {$table}
            WHERE is_pinned = 0
              AND id NOT IN ({$placeholders})
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$ids ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @return array
	 */
	public function getDisabledOlderThan( int $days ): array {
		global $wpdb;

		$table  = $this->tableName();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$sql = "
            SELECT *
            FROM {$table}
            WHERE status = %s
              AND updated_at <= %s
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, self::STATUS_DISABLED, $cutoff ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	public function deleteById( int $id ): void {
		global $wpdb;

		if ( $id <= 0 ) {
			return;
		}

		$table = $this->tableName();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}
}
