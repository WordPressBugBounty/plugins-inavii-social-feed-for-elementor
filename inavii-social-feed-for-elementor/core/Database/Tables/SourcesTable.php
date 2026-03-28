<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

final class SourcesTable extends AbstractTable {

	public const BASE_NAME  = 'inavii_sources';
	public const WPDB_PROP  = 'inavii_sources';
	public const DB_VERSION = 1;

	private const OPT_DB_VERSION = 'inavii_social_feed_db_sources_version';
	private const OPT_DB_FAILED  = 'inavii_social_feed_db_sources_failed';

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
        kind            VARCHAR(32)  NOT NULL,
        source_key      VARCHAR(191) NOT NULL,
        account_id      BIGINT(20) UNSIGNED NULL,
        fetch_key       VARCHAR(191) NOT NULL,
        status          VARCHAR(16)  NOT NULL DEFAULT 'active',
        is_pinned       TINYINT(1)   NOT NULL DEFAULT 0,

        last_sync_at     DATETIME NULL,
        last_success_at  DATETIME NULL,
        last_error       TEXT     NULL,
        sync_attempts    TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
        next_sync_at     DATETIME NULL,

        created_at      DATETIME NOT NULL,
        updated_at      DATETIME NOT NULL,

        PRIMARY KEY (id),
        UNIQUE KEY source_key_uniq (source_key),

        KEY status_idx (status),
        KEY next_sync_idx (next_sync_at),
        KEY kind_idx (kind),
        KEY account_idx (account_id),
        KEY pinned_idx (is_pinned)
    ) ENGINE=InnoDB {$collate};";
	}
}
