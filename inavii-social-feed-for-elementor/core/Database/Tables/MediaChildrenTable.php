<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

final class MediaChildrenTable extends AbstractTable {

	public const BASE_NAME  = 'inavii_media_children';
	public const WPDB_PROP  = 'inavii_media_children';
	public const DB_VERSION = 1;

	private const OPT_DB_VERSION = 'inavii_social_feed_db_media_children_version';
	private const OPT_DB_FAILED  = 'inavii_social_feed_db_media_children_failed';

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
        id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

        parent_id         BIGINT(20) UNSIGNED NOT NULL,
        ig_media_id       VARCHAR(64)  NOT NULL,

        file_path        VARCHAR(255) NULL,
        file_status      VARCHAR(16)  NOT NULL DEFAULT 'none',
        file_error       TEXT         NULL,
        file_updated_at  DATETIME     NULL,
        file_attempts    TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,

        created_at        DATETIME     NOT NULL,
        updated_at        DATETIME     NOT NULL,

        PRIMARY KEY (id),
        UNIQUE KEY parent_media_uniq (parent_id, ig_media_id),

        KEY parent_idx (parent_id),
        KEY file_status_idx (file_status),
        KEY file_updated_idx (file_updated_at)
    ) ENGINE=InnoDB {$collate};";
	}

	/**
	 * @return array{last_error:string,last_query:string}
	 */
	protected function create_or_upgrade(): array {
		return parent::create_or_upgrade();
	}
}
