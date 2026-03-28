<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Storage;

use Inavii\Instagram\Database\Tables\FeedFrontCacheTable;

final class FrontIndexRepository {
	private const PAYLOAD_VERSION = 'v1';

	private FeedFrontCacheTable $table;

	public function __construct( FeedFrontCacheTable $table ) {
		$this->table = $table;
	}

	public function getByFeedId( int $feedId ): ?array {
		$row = $this->getRowByFeedId( $feedId );
		if ( ! is_array( $row ) ) {
			return null;
		}

		$version = isset( $row['payload_version'] ) ? (string) $row['payload_version'] : '';
		if ( $version !== self::PAYLOAD_VERSION ) {
			return null;
		}

		$meta     = $this->decodeAssoc( isset( $row['meta_json'] ) ? (string) $row['meta_json'] : '' );
		$media    = $this->decodeList( isset( $row['media_json'] ) ? (string) $row['media_json'] : '' );
		$mediaIds = $this->decodeIds( isset( $row['media_ids_json'] ) ? (string) $row['media_ids_json'] : '' );

		if ( $meta === null ) {
			return null;
		}

		if ( $mediaIds === [] ) {
			$mediaIds = $this->extractIdsFromMedia( $media );
		}

		return [
			'meta'     => $meta,
			'media'    => $media,
			'mediaIds' => $mediaIds,
		];
	}

	public function save( int $feedId, array $meta, array $media, array $mediaIds ): void {
		if ( $feedId <= 0 ) {
			return;
		}

		$metaJson     = wp_json_encode( $meta );
		$mediaJson    = wp_json_encode( $media );
		$mediaIdsJson = wp_json_encode( $this->normalizeIds( $mediaIds ) );

		if ( ! is_string( $metaJson ) || ! is_string( $mediaJson ) || ! is_string( $mediaIdsJson ) ) {
			return;
		}

		global $wpdb;

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "
			INSERT INTO {$table} (feed_id, meta_json, media_json, media_ids_json, payload_version, updated_at)
			VALUES (%d, %s, %s, %s, %s, %s)
			ON DUPLICATE KEY UPDATE
				meta_json = VALUES(meta_json),
				media_json = VALUES(media_json),
				media_ids_json = VALUES(media_ids_json),
				payload_version = VALUES(payload_version),
				updated_at = VALUES(updated_at)
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$feedId,
				$metaJson,
				$mediaJson,
				$mediaIdsJson,
				self::PAYLOAD_VERSION,
				$now
			)
		);
	}

	public function deleteByFeedId( int $feedId ): void {
		global $wpdb;

		if ( $feedId <= 0 ) {
			return;
		}

		if ( ! $this->table->exists() ) {
			return;
		}

		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, [ 'feed_id' => $feedId ], [ '%d' ] );
	}

	public function clearAll(): void {
		if ( ! $this->table->exists() ) {
			return;
		}

		$table = $this->tableName();
		if ( preg_match( '/^[A-Za-z0-9_]+$/', $table ) !== 1 ) {
			return;
		}

		global $wpdb;
		$query = 'TRUNCATE TABLE `' . $table . '`';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $query );
	}

	private function decodeAssoc( string $json ): ?array {
		if ( trim( $json ) === '' ) {
			return null;
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : null;
	}

	private function decodeList( string $json ): array {
		if ( trim( $json ) === '' ) {
			return [];
		}

		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		return array_values(
			array_filter(
				$decoded,
				static function ( $row ): bool {
					return is_array( $row );
				}
			)
		);
	}

	private function decodeIds( string $json ): array {
		if ( trim( $json ) === '' ) {
			return [];
		}

		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		return $this->normalizeIds( $decoded );
	}

	private function normalizeIds( array $ids ): array {
		$out = [];

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 ) {
				$out[] = $id;
			}
		}

		return array_values( array_unique( $out ) );
	}

	private function extractIdsFromMedia( array $media ): array {
		$ids = [];
		foreach ( $media as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	private function tableName(): string {
		return $this->table->table_name();
	}

	private function getRowByFeedId( int $feedId ): ?array {
		global $wpdb;

		if ( $feedId <= 0 ) {
			return null;
		}

		$table = $this->tableName();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$table} WHERE feed_id = %d LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $sql, $feedId ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}
}
