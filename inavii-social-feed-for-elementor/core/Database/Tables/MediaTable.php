<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

final class MediaTable extends AbstractTable {

	public const BASE_NAME  = 'inavii_media';
	public const WPDB_PROP  = 'inavii_media';
	public const DB_VERSION = 1;

	private const OPT_DB_VERSION = 'inavii_social_feed_db_media_version';
	private const OPT_DB_FAILED  = 'inavii_social_feed_db_media_failed';

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
        id                 BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

        source_key         VARCHAR(191) NOT NULL,
        ig_media_id        VARCHAR(64)  NOT NULL,

        media_type         VARCHAR(32)  NOT NULL DEFAULT '',
        media_product_type VARCHAR(32)  NULL,

        url                TEXT         NOT NULL,
        permalink          TEXT         NOT NULL,

        username           VARCHAR(191) NOT NULL DEFAULT '',
        video_url          TEXT         NULL,

        posted_at          DATETIME     NOT NULL,
        comments_count     INT(10) UNSIGNED NOT NULL DEFAULT 0,
        likes_count        INT(10) UNSIGNED NOT NULL DEFAULT 0,

        caption            LONGTEXT     NOT NULL,
        children_json      LONGTEXT     NULL,

        file_path         VARCHAR(255) NULL,
        file_thumb_path   VARCHAR(255) NULL,
        file_status       VARCHAR(16)  NOT NULL DEFAULT 'none',
        file_error        TEXT         NULL,
        file_updated_at   DATETIME     NULL,
        file_attempts     TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,

        last_requested_at  DATETIME     NULL,
        last_seen_at       DATETIME     NULL,

        created_at         DATETIME     NOT NULL,
        updated_at         DATETIME     NOT NULL,

        PRIMARY KEY  (id),
        UNIQUE KEY source_media_uniq (source_key, ig_media_id),

        KEY source_posted_at_idx (source_key, posted_at),
        KEY last_requested_idx (last_requested_at),
        KEY last_seen_idx (last_seen_at),
        KEY posted_at_idx (posted_at),

        KEY file_status_idx (file_status),
        KEY file_updated_idx (file_updated_at),
        KEY file_queue_idx (file_status, file_updated_at, id)
    ) ENGINE=InnoDB {$collate};";
	}
}
