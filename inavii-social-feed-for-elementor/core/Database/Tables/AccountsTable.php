<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

final class AccountsTable extends AbstractTable {

	public const BASE_NAME  = 'inavii_accounts';
	public const WPDB_PROP  = 'inavii_accounts';
	public const DB_VERSION = 1;

	private const OPT_DB_VERSION = 'inavii_social_feed_db_accounts_version';
	private const OPT_DB_FAILED  = 'inavii_social_feed_db_accounts_failed';

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

            ig_account_id      VARCHAR(64)  NULL,
            account_type       VARCHAR(32)  NOT NULL DEFAULT '',
            connect_type       VARCHAR(32)  NOT NULL DEFAULT '',
            name               VARCHAR(255) NOT NULL DEFAULT '',
            username           VARCHAR(191) NOT NULL DEFAULT '',
            access_token       LONGTEXT     NOT NULL,
            access_token_iv    VARCHAR(255) NOT NULL DEFAULT '',
            avatar             TEXT         NOT NULL,
            biography          LONGTEXT     NOT NULL,

            media_count        INT(10) UNSIGNED NOT NULL DEFAULT 0,
            followers_count    INT(10) UNSIGNED NOT NULL DEFAULT 0,
            follows_count      INT(10) UNSIGNED NOT NULL DEFAULT 0,

            token_expires      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            token_refresh_attempt_at DATETIME NULL,
            last_update        VARCHAR(32)  NULL,

            created_at         DATETIME     NOT NULL,
            updated_at         DATETIME     NOT NULL,

            PRIMARY KEY  (id),
            UNIQUE KEY ig_account_id_uniq (ig_account_id)
        ) ENGINE=InnoDB {$collate};";
	}
}
