<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

final class LogsTable extends AbstractTable {

	public const BASE_NAME  = 'inavii_logs';
	public const WPDB_PROP  = 'inavii_logs';
	public const DB_VERSION = 1;

	private const OPT_DB_VERSION = 'inavii_social_feed_db_logs_version';
	private const OPT_DB_FAILED  = 'inavii_social_feed_db_logs_failed';

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
        id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        level        VARCHAR(16) NOT NULL DEFAULT 'info',
        component    VARCHAR(64) NOT NULL DEFAULT '',
        message      TEXT        NOT NULL,
        context_json LONGTEXT    NULL,
        created_at   DATETIME    NOT NULL,
        PRIMARY KEY  (id),
        KEY level_idx (level),
        KEY component_idx (component),
        KEY created_idx (created_at)
    ) ENGINE=InnoDB {$collate};";
	}
}
