<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Storage;

use Inavii\Instagram\Database\Tables\MediaTable;
use Inavii\Instagram\Logger\Logger;
use Inavii\Instagram\Media\Domain\MediaPost;

/**
 * Very small persistence layer for wp_inavii_media.
 *
 * - Upserts by UNIQUE (source_key, ig_media_id)
 * - Does not touch file_* cache fields on update
 */
final class MediaPostsRepository {
	private const SOURCE_MIX_OVERALL  = 'overall';
	private const SOURCE_MIX_BALANCED = 'balanced';
	private const ORDER_TOP_RECENT    = 'top_recent';
	private const TOP_RECENT_RATIO    = 0.1;
	private const TOP_RECENT_MAX_COUNT = 3;

	private const INSERT_COLUMNS = [
		'source_key',
		'ig_media_id',
		'media_type',
		'media_product_type',
		'url',
		'permalink',
		'username',
		'video_url',
		'posted_at',
		'comments_count',
		'likes_count',
		'caption',
		'children_json',
		'last_seen_at',
		'created_at',
		'updated_at',
	];

	private const UPDATE_COLUMNS = [
		'media_type',
		'media_product_type',
		'url',
		'permalink',
		'username',
		'video_url',
		'posted_at',
		'comments_count',
		'likes_count',
		'caption',
		'children_json',
		'last_seen_at',
		'updated_at',
	];

	private const INSERT_PLACEHOLDERS = [
		'%s', // source_key
		'%s', // ig_media_id
		'%s', // media_type
		'%s', // media_product_type
		'%s', // url
		'%s', // permalink
		'%s', // username
		'%s', // video_url
		'%s', // posted_at
		'%d', // comments_count
		'%d', // likes_count
		'%s', // caption
		'%s', // children_json
		'%s', // last_seen_at
		'%s', // created_at
		'%s', // updated_at
	];

	private MediaTable $mediaTable;
	private MediaProFilterSqlBuilder $proFilterSqlBuilder;

	public function __construct( MediaTable $mediaTable, MediaProFilterSqlBuilder $proFilterSqlBuilder ) {
		$this->mediaTable          = $mediaTable;
		$this->proFilterSqlBuilder = $proFilterSqlBuilder;
	}

	private function tableName(): string {
		return $this->mediaTable->table_name();
	}

	private function ensureTable(): bool {
		return $this->mediaTable->ensureExists();
	}

	/**
	 * @param array|MediaPost> $rows DB-row arrays (snake_case columns), WITHOUT source_key
	 */
	public function save( string $sourceKey, array $rows ): int {
		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		if ( $sourceKey === '' || $rows === [] ) {
			return 0;
		}
		if ( ! $this->ensureTable() ) {
			return 0;
		}

		global $wpdb;

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		$savedCount = 0;

		$columnsSql      = implode( ",\n                ", self::INSERT_COLUMNS );
		$placeholdersSql = implode( ', ', self::INSERT_PLACEHOLDERS );
		$updateSql       = $this->buildUpdateSql();

		foreach ( $rows as $row ) {
			if ( $row instanceof MediaPost ) {
				$row = $row->toDbRow();
			}

			if ( ! is_array( $row ) ) {
				continue;
			}

			$params = $this->buildSaveParams( $row, $sourceKey, $now );
			if ( $params === null ) {
				continue;
			}

			$sql = "INSERT INTO {$table} (
                {$columnsSql}
            ) VALUES (
                {$placeholdersSql}
            )
            ON DUPLICATE KEY UPDATE
                {$updateSql}";

			$prepared = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$params
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$affected = $wpdb->query( $prepared );

			if ( $affected === false ) {
				$this->logDbError( 'upsert', $wpdb->last_error );
				continue;
			}

			// MySQL: insert=1, update=2, no-op=0
			if ( (int) $affected > 0 ) {
				$savedCount++;
			}
		}

		return $savedCount;
	}

	/**
	 * @return array
	 */
	public function getBySourceKey(
		string $sourceKey,
		int $limit = 30,
		?string $cursorPostedAt = null,
		?int $cursorId = null
	): array {
		global $wpdb;

		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		if ( $sourceKey === '' ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$limit = $this->clampLimit( $limit );
		$table = $this->tableName();

		$whereSql = 'WHERE source_key = %s';
		$params   = [ $sourceKey ];

		if ( $cursorPostedAt !== null && $cursorPostedAt !== '' && $cursorId !== null && $cursorId > 0 ) {
			$whereSql .= ' AND (posted_at < %s OR (posted_at = %s AND id < %d))';
			$params[]  = $cursorPostedAt;
			$params[]  = $cursorPostedAt;
			$params[]  = $cursorId;
		}

		$sql = "
            SELECT *
            FROM {$table}
            {$whereSql}
            ORDER BY posted_at DESC, id DESC
            LIMIT %d
        ";

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		$rows = is_array( $rows ) ? $rows : [];

		$this->markRequestedByRows( $rows );

		return $rows;
	}

	/**
	 * @param string[] $sourceKeys
	 * @return array
	 */
	public function getBySourceKeys( array $sourceKeys, int $limit = 30, int $offset = 0 ): array {
		return $this->getBySourceKeysFiltered( $sourceKeys, [], $limit, $offset );
	}

	/**
	 * @param string[] $sourceKeys
	 * @return array
	 */
	public function getBySourceKeysFiltered( array $sourceKeys, array $filters, int $limit = 30, int $offset = 0 ): array {
		$keys = $this->normalizeSourceKeys( $sourceKeys );
		if ( $keys === [] ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$limit             = $this->clampLimit( $limit );
		$offset            = max( 0, (int) $offset );
		$normalizedFilters = $this->normalizeFilterArgs( $filters );
		$sourceMixMode     = $normalizedFilters['sourceMixMode'];

		if ( $sourceMixMode === self::SOURCE_MIX_BALANCED && count( $keys ) > 1 ) {
			$rows = $this->queryBySourceKeysFilteredBalanced( $keys, $normalizedFilters, $limit, $offset );
		} else {
			$rows = $this->queryBySourceKeysFilteredDistinct( $keys, $normalizedFilters, $limit, $offset );
		}

		$this->markRequestedByRows( $rows );

		return $rows;
	}

	/**
	 * @param int[] $ids
	 * @return array
	 */
	public function getByIds( array $ids ): array {
		global $wpdb;

		$filtered = [];
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 ) {
				$filtered[] = $id;
			}
		}

		$filtered = array_values( array_unique( $filtered ) );
		if ( $filtered === [] ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$table             = $this->tableName();
		$inPlaceholders    = implode( ',', array_fill( 0, count( $filtered ), '%d' ) );
		$fieldPlaceholders = implode( ',', array_fill( 0, count( $filtered ), '%d' ) );

		$sql = "
            SELECT *
            FROM {$table}
            WHERE id IN ({$inPlaceholders})
            ORDER BY FIELD(id, {$fieldPlaceholders})
        ";

		$params = array_merge( $filtered, $filtered );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		$rows = is_array( $rows ) ? $rows : [];

		$this->markRequestedByRows( $rows );

		return $rows;
	}

	public function countBySourceKeys( array $sourceKeys ): int {
		return $this->countBySourceKeysFiltered( $sourceKeys, [] );
	}

	public function countBySourceKeysFiltered( array $sourceKeys, array $filters ): int {
		$keys = $this->normalizeSourceKeys( $sourceKeys );
		if ( $keys === [] ) {
			return 0;
		}
		if ( ! $this->ensureTable() ) {
			return 0;
		}

		$normalizedFilters = $this->normalizeFilterArgs( $filters );

		return $this->queryCountDistinctBySourceKeysFiltered( $keys, $normalizedFilters );
	}

	private function queryBySourceKeysFilteredDistinct( array $keys, array $filters, int $limit, int $offset ): array {
		if ( $this->isTopRecentOrder( $filters ) ) {
			return $this->queryBySourceKeysFilteredTopRecentDistinct( $keys, $filters, $limit, $offset );
		}

		return $this->queryBySourceKeysFilteredDistinctDirect( $keys, $filters, $limit, $offset );
	}

	private function queryBySourceKeysFilteredDistinctDirect( array $keys, array $filters, int $limit, int $offset ): array {
		global $wpdb;

		$table        = $this->tableName();
		$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
		$whereSql     = "WHERE source_key IN ({$placeholders})";
		$params       = $keys;

		$whereSql .= $this->buildFiltersWhereSql( $filters, $params );
		$orderBySql = $this->buildOrderBySqlWithAlias( $filters, 'm' );

		$sql = "
			SELECT m.*
			FROM {$table} m
			INNER JOIN (
				SELECT MAX(id) AS id
				FROM {$table}
				{$whereSql}
				GROUP BY ig_media_id
			) uniq ON uniq.id = m.id
			{$orderBySql}
			LIMIT %d OFFSET %d
        ";

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	private function queryBySourceKeysFilteredTopRecentDistinct( array $keys, array $filters, int $limit, int $offset ): array {
		$recentFilters            = $filters;
		$recentFilters['orderBy'] = 'recent';

		$total = $this->queryCountDistinctBySourceKeysFiltered( $keys, $recentFilters );
		if ( $total <= 0 ) {
			return [];
		}

		$allRows = $this->queryBySourceKeysFilteredDistinctDirect( $keys, $recentFilters, $total, 0 );
		if ( $allRows === [] ) {
			return [];
		}

		$ordered = $this->orderTopRecentRows( $allRows, $limit );

		return array_slice( $ordered, $offset, $limit );
	}

	private function queryCountDistinctBySourceKeysFiltered( array $keys, array $filters ): int {
		global $wpdb;

		$table        = $this->tableName();
		$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
		$whereSql     = "WHERE source_key IN ({$placeholders})";
		$params       = $keys;

		$whereSql .= $this->buildFiltersWhereSql( $filters, $params );

		$sql = "
            SELECT COUNT(DISTINCT ig_media_id)
            FROM {$table}
            {$whereSql}
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );

		return (int) $count;
	}

	private function queryBySourceKeysFilteredBalanced( array $keys, array $filters, int $limit, int $offset ): array {
		$target = $offset + $limit;
		if ( $target <= 0 ) {
			return [];
		}

		$batchSize    = min( 100, max( 20, $limit ) );
		$sourceStates = [];
		$baseFilters  = $filters;
		$baseFilters['sourceMixMode'] = self::SOURCE_MIX_OVERALL;

		foreach ( $keys as $key ) {
			$total = $this->queryCountDistinctBySourceKeysFiltered( [ $key ], $baseFilters );
			$sourceStates[ $key ] = [
				'total'    => $total,
				'offset'   => 0,
				'cursor'   => 0,
				'exhausted' => $total <= 0,
				'rows'     => [],
			];
		}

		$merged      = [];
		$seenMediaId = [];
		$mergedCount = 0;

		while ( $mergedCount < $target ) {
			$addedInRound = false;

			foreach ( $keys as $key ) {
				$next = $this->takeNextBalancedItem( $key, $sourceStates, $baseFilters, $batchSize, $seenMediaId );
				if ( $next === null ) {
					continue;
				}

				$merged[]      = $next;
				$mergedCount++;
				$addedInRound  = true;

				if ( $mergedCount >= $target ) {
					break;
				}
			}

			if ( ! $addedInRound ) {
				break;
			}
		}

		return array_slice( $merged, $offset, $limit );
	}

	private function takeNextBalancedItem(
		string $sourceKey,
		array &$sourceStates,
		array $baseFilters,
		int $batchSize,
		array &$seenMediaId
	): ?array {
		while ( true ) {
			$state = $sourceStates[ $sourceKey ];
			if ( $state['exhausted'] ) {
				return null;
			}

			if ( $state['cursor'] >= count( $state['rows'] ) ) {
				if ( $state['offset'] >= $state['total'] ) {
					$sourceStates[ $sourceKey ]['exhausted'] = true;
					return null;
				}

				$rows = $this->queryBySourceKeysFilteredDistinct(
					[ $sourceKey ],
					$baseFilters,
					$batchSize,
					$state['offset']
				);

				if ( $rows === [] ) {
					$sourceStates[ $sourceKey ]['exhausted'] = true;
					return null;
				}

				$sourceStates[ $sourceKey ]['rows']    = array_merge( $state['rows'], $rows );
				$sourceStates[ $sourceKey ]['offset'] += count( $rows );
				$state = $sourceStates[ $sourceKey ];
			}

			$row = $state['rows'][ $state['cursor'] ];
			$sourceStates[ $sourceKey ]['cursor'] = $state['cursor'] + 1;

			$mediaId = isset( $row['ig_media_id'] ) ? trim( (string) $row['ig_media_id'] ) : '';
			if ( $mediaId === '' || isset( $seenMediaId[ $mediaId ] ) ) {
				continue;
			}

			$seenMediaId[ $mediaId ] = true;
			return $row;
		}
	}

	public function countBySourceKey( string $sourceKey ): int {
		global $wpdb;

		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		if ( $sourceKey === '' ) {
			return 0;
		}
		if ( ! $this->ensureTable() ) {
			return 0;
		}

		$table = $this->tableName();

		$sql = "
            SELECT COUNT(1)
            FROM {$table}
            WHERE source_key = %s
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $sourceKey ) );

		return (int) $count;
	}

	/**
	 * @param string[] $sourceKeys
	 * @return array
	 */
	public function getLastSeenAtBySourceKeys( array $sourceKeys ): array {
		global $wpdb;

		$sourceKeys = $this->normalizeSourceKeys( $sourceKeys );
		if ( $sourceKeys === [] ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$table        = $this->tableName();
		$placeholders = implode( ', ', array_fill( 0, count( $sourceKeys ), '%s' ) );

		$sql = "
            SELECT source_key, MAX(last_seen_at) AS last_seen_at
            FROM {$table}
            WHERE source_key IN ({$placeholders})
            GROUP BY source_key
        ";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $sourceKeys ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$map = [];
		foreach ( $rows as $row ) {
			$key = isset( $row['source_key'] ) ? trim( (string) $row['source_key'] ) : '';
			if ( $key === '' ) {
				continue;
			}

			$map[ $key ] = isset( $row['last_seen_at'] ) ? (string) $row['last_seen_at'] : '';
		}

		return $map;
	}

	public function getOldestFilesBySourceKey( string $sourceKey, int $limit ): array {
		global $wpdb;

		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		if ( $sourceKey === '' ) {
			return [];
		}

		$limit = max( 0, (int) $limit );
		if ( $limit <= 0 ) {
			return [];
		}

		if ( ! $this->ensureTable() ) {
			return [];
		}

		$table = $this->tableName();

		$sql = "
            SELECT id, file_path, file_thumb_path
            FROM {$table}
            WHERE source_key = %s
            ORDER BY posted_at ASC, id ASC
            LIMIT %d
        ";

		$params = [ $sourceKey, $limit ];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @return array
	 */
	public function getFilesBySourceKey( string $sourceKey ): array {
		global $wpdb;

		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		if ( $sourceKey === '' ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$table = $this->tableName();

		$sql = "
            SELECT id, file_path, file_thumb_path
            FROM {$table}
            WHERE source_key = %s
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $sourceKey ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param array $rows
	 */
	private function markRequestedByRows( array $rows ): void {
		$ids = [];
		foreach ( $rows as $row ) {
			if ( isset( $row['id'] ) ) {
				$id = (int) $row['id'];
				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}
		}

		$this->markRequestedByIds( $ids );
	}

	/**
	 * @param int[] $ids
	 */
	private function markRequestedByIds( array $ids ): void {
		if ( $ids === [] ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		global $wpdb;

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "UPDATE {$table} SET last_requested_at = %s WHERE id IN ({$placeholders})";
		$params       = array_merge( [ $now ], $ids );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * @return array
	 */
	public function getFilesBySourceKeySeenBefore( string $sourceKey, string $cutoff ): array {
		global $wpdb;

		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		$cutoff    = trim( $cutoff );
		if ( $sourceKey === '' || $cutoff === '' ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$table = $this->tableName();

		$sql = "
            SELECT id, file_path, file_thumb_path
            FROM {$table}
            WHERE source_key = %s
              AND last_seen_at < %s
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $sourceKey, $cutoff ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param string[] $seenMediaIds
	 * @return array
	 */
	public function getMissingBySourceKeySince(
		string $sourceKey,
		array $seenMediaIds,
		string $postedAtFrom,
		int $limit = 500
	): array {
		global $wpdb;

		$sourceKey    = $this->normalizeSourceKey( $sourceKey );
		$postedAtFrom = trim( $postedAtFrom );
		$limit        = max( 1, min( 5000, (int) $limit ) );

		$seen = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $value ): string {
							return trim( (string) $value );
						},
						$seenMediaIds
					),
					static function ( string $value ): bool {
						return $value !== '';
					}
				)
			)
		);

		if ( $sourceKey === '' || $postedAtFrom === '' || $seen === [] ) {
			return [];
		}
		if ( ! $this->ensureTable() ) {
			return [];
		}

		$table        = $this->tableName();
		$placeholders = implode( ',', array_fill( 0, count( $seen ), '%s' ) );

		$sql = "
			SELECT id, ig_media_id, file_path, file_thumb_path
			FROM {$table}
			WHERE source_key = %s
			  AND posted_at >= %s
			  AND ig_media_id NOT IN ({$placeholders})
			ORDER BY posted_at DESC, id DESC
			LIMIT %d
		";

		$params = array_merge( [ $sourceKey, $postedAtFrom ], $seen, [ $limit ] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	public function deleteBySourceKey( string $sourceKey ): void {
		global $wpdb;

		$sourceKey = $this->normalizeSourceKey( $sourceKey );
		if ( $sourceKey === '' ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		$table = $this->tableName();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE source_key = %s", $sourceKey ) );
	}

	/**
	 * @param array $ids
	 */
	public function deleteByIds( array $ids ): void {
		global $wpdb;

		$filtered = [];
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 ) {
				$filtered[] = $id;
			}
		}

		if ( $filtered === [] ) {
			return;
		}
		if ( ! $this->ensureTable() ) {
			return;
		}

		$table        = $this->tableName();
		$placeholders = implode( ',', array_fill( 0, count( $filtered ), '%d' ) );
		$sql          = "DELETE FROM {$table} WHERE id IN ({$placeholders})";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $sql, $filtered ) );
	}

	private function normalizeSourceKeys( array $sourceKeys ): array {
		$keys = [];
		foreach ( $sourceKeys as $k ) {
			$k = trim( (string) $k );
			if ( $k !== '' ) {
				$keys[] = $k;
			}
		}

		return array_values( array_unique( $keys ) );
	}

	private function normalizeFilterArgs( array $filters ): array {
		$defaults = [
			'orderBy'           => 'recent',
			'sourceMixMode'     => self::SOURCE_MIX_OVERALL,
			'typesOfPosts'      => [ 'IMAGE', 'VIDEO', 'CAROUSEL_ALBUM' ],
			'captionInclude'    => [],
			'captionExclude'    => [],
			'hashtagInclude'    => [],
			'hashtagExclude'    => [],
			'moderationEnabled' => false,
			'moderationMode'    => 'hide',
			'moderationPostIds' => [],
		];

		$normalized = array_merge( $defaults, $filters );

		foreach ( [ 'typesOfPosts', 'captionInclude', 'captionExclude', 'hashtagInclude', 'hashtagExclude', 'moderationPostIds' ] as $key ) {
			if ( ! is_array( $normalized[ $key ] ) ) {
				$normalized[ $key ] = [];
			}
		}

		$normalized['orderBy'] = is_string( $normalized['orderBy'] ) ? strtolower( trim( $normalized['orderBy'] ) ) : 'recent';
		$normalized['sourceMixMode'] = $this->normalizeSourceMixMode( $normalized['sourceMixMode'] );
		$normalized['moderationMode'] = ( is_string( $normalized['moderationMode'] ) && strtolower( trim( $normalized['moderationMode'] ) ) === 'show' ) ? 'show' : 'hide';
		$normalized['moderationEnabled'] = (bool) $normalized['moderationEnabled'];

		$normalized['typesOfPosts'] = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $type ): string {
							return strtoupper( trim( (string) $type ) );
						},
						$normalized['typesOfPosts']
					),
					static function ( string $type ): bool {
						return in_array( $type, [ 'IMAGE', 'VIDEO', 'CAROUSEL_ALBUM' ], true );
					}
				)
			)
		);

		foreach ( [ 'captionInclude', 'captionExclude', 'hashtagInclude', 'hashtagExclude', 'moderationPostIds' ] as $key ) {
			$normalized[ $key ] = array_values(
				array_unique(
					array_filter(
						array_map(
							static function ( $value ): string {
								return trim( (string) $value );
							},
							$normalized[ $key ]
						),
						static function ( string $value ): bool {
							return $value !== '';
						}
					)
				)
			);
		}

		$normalized['hashtagInclude'] = array_values(
			array_unique(
				array_map(
					static function ( string $value ): string {
						return ltrim( strtolower( $value ), '#' );
					},
					$normalized['hashtagInclude']
				)
			)
		);

		$normalized['hashtagExclude'] = array_values(
			array_unique(
				array_map(
					static function ( string $value ): string {
						return ltrim( strtolower( $value ), '#' );
					},
					$normalized['hashtagExclude']
				)
			)
		);

		return $normalized;
	}

	private function buildOrderBySql( array $filters ): string {
		return $this->buildOrderBySqlWithAlias( $filters, '' );
	}

	private function buildOrderBySqlWithAlias( array $filters, string $alias ): string {
		$prefix = $alias !== '' ? $alias . '.' : '';

		switch ( $filters['orderBy'] ) {
			case 'popular':
				return 'ORDER BY (COALESCE(' . $prefix . 'likes_count, 0) + COALESCE(' . $prefix . 'comments_count, 0)) DESC, ' . $prefix . 'posted_at DESC, ' . $prefix . 'id DESC';
			case 'likes':
				return 'ORDER BY ' . $prefix . 'likes_count DESC, ' . $prefix . 'posted_at DESC, ' . $prefix . 'id DESC';
			case 'comments':
				return 'ORDER BY ' . $prefix . 'comments_count DESC, ' . $prefix . 'posted_at DESC, ' . $prefix . 'id DESC';
			case self::ORDER_TOP_RECENT:
			case 'recent':
			default:
				return 'ORDER BY ' . $prefix . 'posted_at DESC, ' . $prefix . 'id DESC';
		}
	}

	private function isTopRecentOrder( array $filters ): bool {
		return isset( $filters['orderBy'] ) && $filters['orderBy'] === self::ORDER_TOP_RECENT;
	}

	private function orderTopRecentRows( array $rows, int $windowSize ): array {
		$totalRows = count( $rows );
		if ( $totalRows <= 1 ) {
			return $rows;
		}

		$topCount = $this->resolveTopRecentCount( $totalRows, $windowSize );
		if ( $topCount <= 0 ) {
			return $rows;
		}

		$topRows = $rows;
		usort(
			$topRows,
			static function ( array $a, array $b ): int {
				$scoreA = (int) ( $a['likes_count'] ?? 0 ) + (int) ( $a['comments_count'] ?? 0 );
				$scoreB = (int) ( $b['likes_count'] ?? 0 ) + (int) ( $b['comments_count'] ?? 0 );

				if ( $scoreA !== $scoreB ) {
					return $scoreB <=> $scoreA;
				}

				$postedA = isset( $a['posted_at'] ) ? (string) $a['posted_at'] : '';
				$postedB = isset( $b['posted_at'] ) ? (string) $b['posted_at'] : '';
				if ( $postedA !== $postedB ) {
					return strcmp( $postedB, $postedA );
				}

				return (int) ( $b['id'] ?? 0 ) <=> (int) ( $a['id'] ?? 0 );
			}
		);

		$topRows = array_slice( $topRows, 0, $topCount );
		$topIds  = [];
		foreach ( $topRows as $row ) {
			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $id > 0 ) {
				$topIds[ $id ] = true;
			}
		}

		$recentRows = [];
		foreach ( $rows as $row ) {
			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $id > 0 && isset( $topIds[ $id ] ) ) {
				continue;
			}

			$recentRows[] = $row;
		}

		return array_merge( $topRows, $recentRows );
	}

	private function resolveTopRecentCount( int $totalRows, int $windowSize ): int {
		if ( $totalRows <= 0 ) {
			return 0;
		}

		$ratio = (float) apply_filters( 'inavii/social-feed/media/top_recent_ratio', self::TOP_RECENT_RATIO );
		if ( $ratio <= 0 || $ratio > 1 ) {
			$ratio = self::TOP_RECENT_RATIO;
		}

		$window = max( 1, min( $totalRows, (int) $windowSize ) );
		$count  = (int) round( $window * $ratio );
		if ( $count < 1 ) {
			$count = 1;
		}
		if ( $count > self::TOP_RECENT_MAX_COUNT ) {
			$count = self::TOP_RECENT_MAX_COUNT;
		}
		if ( $count > $totalRows ) {
			$count = $totalRows;
		}

		return $count;
	}

	private function normalizeSourceMixMode( $value ): string {
		if ( ! is_string( $value ) ) {
			return self::SOURCE_MIX_OVERALL;
		}

		$mode = strtolower( trim( $value ) );
		if ( $mode === self::SOURCE_MIX_BALANCED ) {
			return self::SOURCE_MIX_BALANCED;
		}

		return self::SOURCE_MIX_OVERALL;
	}

	private function buildFiltersWhereSql( array $filters, array &$params ): string {
		$where = '';

		if ( array_key_exists( 'typesOfPosts', $filters ) ) {
			if ( $filters['typesOfPosts'] === [] ) {
				return ' AND 1=0';
			}

			$placeholders = implode( ',', array_fill( 0, count( $filters['typesOfPosts'] ), '%s' ) );
			$where       .= " AND media_type IN ({$placeholders})";
			$params       = array_merge( $params, $filters['typesOfPosts'] );
		}

		$where .= $this->proFilterSqlBuilder->buildWhereSql( $filters, $params );

		return $where;
	}

	private function clampLimit( int $limit ): int {
		if ( $limit < 1 ) {
			return 1;
		}
		if ( $limit > 100 ) {
			return 100;
		}
		return $limit;
	}

	private function normalizeSourceKey( string $sourceKey ): string {
		return trim( $sourceKey );
	}

	private function buildUpdateSql(): string {
		$updates = [];
		foreach ( self::UPDATE_COLUMNS as $column ) {
			$updates[] = $column . ' = VALUES(' . $column . ')';
		}

		return implode( ",\n                ", $updates );
	}

	/**
	 * @param array $row Normalized DB-row (snake_case columns)
	 * @return array|null
	 */
	private function buildSaveParams( array $row, string $sourceKey, string $now ): ?array {
		$igMediaId = (string) ( $row['ig_media_id'] ?? '' );
		if ( $igMediaId === '' ) {
			return null;
		}

		$postedAt = (string) ( $row['posted_at'] ?? '' );
		if ( $postedAt === '' ) {
			return null;
		}

		return [
			$sourceKey,
			$igMediaId,
			$row['media_type'] ?? '',
			$row['media_product_type'] ?? '',
			$row['url'] ?? '',
			$row['permalink'] ?? '',
			$row['username'] ?? '',
			$row['video_url'] ?? '',
			$postedAt,
			$row['comments_count'] ?? 0,
			$row['likes_count'] ?? 0,
			$row['caption'] ?? '',
			$row['children_json'] ?? '',
			$now, // last_seen_at
			$now, // created_at (only used on insert)
			$now, // updated_at
		];
	}

	private function logDbError( string $action, string $error ): void {
		if ( $error === '' ) {
			return;
		}

		Logger::error(
			'db/media_posts',
			'Media posts DB ' . $action . ' failed.',
			[
				'error' => $error,
			]
		);
	}
}
