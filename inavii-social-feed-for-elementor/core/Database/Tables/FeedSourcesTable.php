<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

final class FeedSourcesTable extends AbstractTable {

	public const BASE_NAME  = 'inavii_feed_sources';
	public const WPDB_PROP  = 'inavii_feed_sources';
	public const DB_VERSION = 1;

	private const OPT_DB_VERSION = 'inavii_social_feed_db_feed_sources_version';
	private const OPT_DB_FAILED  = 'inavii_social_feed_db_feed_sources_failed';

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
        id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        feed_id    BIGINT(20) UNSIGNED NOT NULL,
        source_id  BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,

        PRIMARY KEY (id),
        UNIQUE KEY feed_source_uniq (feed_id, source_id),
        KEY feed_idx (feed_id),
        KEY source_idx (source_id)
    ) ENGINE=InnoDB {$collate};";
	}
}
