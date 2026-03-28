<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

final class FeedFrontCacheTable extends AbstractTable {

	public const BASE_NAME  = 'inavii_feed_front_cache';
	public const WPDB_PROP  = 'inavii_feed_front_cache';
	public const DB_VERSION = 1;

	private const OPT_DB_VERSION = 'inavii_social_feed_db_feed_front_cache_version';
	private const OPT_DB_FAILED  = 'inavii_social_feed_db_feed_front_cache_failed';

	protected function baseName(): string {
		return self::BASE_NAME;
	}

	protected function wpdbProp(): string {
		return self::WPDB_PROP;
	}

	protected function dbVersion(): int {
		return self::DB_VERSION;
	}

	protected function optDbVersion(): string {
		return self::OPT_DB_VERSION;
	}

	protected function optDbFailed(): string {
		return self::OPT_DB_FAILED;
	}

	protected function schemaSql( string $table, string $collate ): string {
		return "CREATE TABLE {$table} (
        id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        feed_id         BIGINT(20) UNSIGNED NOT NULL,
        meta_json       LONGTEXT NOT NULL,
        media_json      LONGTEXT NULL,
        media_ids_json  LONGTEXT NULL,
        payload_version VARCHAR(16) NOT NULL DEFAULT 'v1',
        updated_at      DATETIME NOT NULL,

        PRIMARY KEY (id),
        UNIQUE KEY feed_id_uniq (feed_id),
        KEY updated_idx (updated_at)
    ) ENGINE=InnoDB {$collate};";
	}
}
