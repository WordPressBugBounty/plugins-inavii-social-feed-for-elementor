<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Storage;

use Inavii\Instagram\Account\Dto\AccountCredentials;
use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Token\AccessTokenCipher;
use Inavii\Instagram\Database\Tables\AccountsTable;
use Inavii\Instagram\Logger\Logger;

final class AccountRepository {
	private AccountsTable $accountsTable;
	private AccessTokenCipher $cipher;
	private ?bool $hasAccessTokenIvColumn         = null;
	private ?bool $hasTokenRefreshAttemptAtColumn = null;

	public function __construct(
		AccountsTable $accountsTable,
		AccessTokenCipher $cipher
	) {
		$this->accountsTable = $accountsTable;
		$this->cipher        = $cipher;
	}

	public function create( Account $account ): int {
		return $this->save( $account );
	}

	public function save( Account $account ): int {
		if ( ! $this->ensureTable() ) {
			return 0;
		}

		$id = (int) $account->id();

		return $id > 0
			? $this->update( $id, $account )
			: $this->insert( $account );
	}

	public function findByIgAccountId( string $igAccountId ): ?Account {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return null;
		}

		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE ig_account_id = %s LIMIT 1", $igAccountId ),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		$id = (int) ( $row['id'] ?? 0 );

		return $id > 0 ? Account::fromArray( $this->dbRowToDomainArray( $row ) ) : null;
	}

	public function findByUsername( string $username ): ?Account {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return null;
		}

		$username = trim( $username );
		if ( $username === '' ) {
			return null;
		}

		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE LOWER(username) = LOWER(%s) LIMIT 1", $username ),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		$id = (int) ( $row['id'] ?? 0 );

		return $id > 0 ? Account::fromArray( $this->dbRowToDomainArray( $row ) ) : null;
	}

	public function findBusinessAccount(): ?Account {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return null;
		}

		$table = $this->tableName();
		$sql   = "
			SELECT *
			FROM {$table}
			WHERE (
				connect_type = 'facebook'
				OR (connect_type = '' AND account_type IN ('business'))
			)
			ORDER BY id DESC
			LIMIT 1
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $sql, ARRAY_A );

		if ( ! is_array( $row ) ) {
			return null;
		}

		$id = (int) ( $row['id'] ?? 0 );

		return $id > 0 ? Account::fromArray( $this->dbRowToDomainArray( $row ) ) : null;
	}

	/**
	 * @return Account[]
	 */
	public function expiringFacebookAccounts( int $thresholdSeconds, int $now ): array {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return [];
		}

		$thresholdSeconds = max( 0, $thresholdSeconds );
		$cutoff           = $now + $thresholdSeconds;
		if ( $cutoff <= 0 ) {
			return [];
		}

		$table = $this->tableName();
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table}
			 WHERE connect_type = 'facebook'
			   AND token_expires > 0
			   AND token_expires <= %d
			 ORDER BY token_expires ASC",
			$cutoff
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) || $rows === [] ) {
			return [];
		}

		$out = [];
		foreach ( $rows as $row ) {
			$id = (int) ( $row['id'] ?? 0 );
			if ( $id > 0 ) {
				$out[] = Account::fromArray( $this->dbRowToDomainArray( $row ) );
			}
		}

		return $out;
	}

	public function get( int $id ): Account {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			throw new \RuntimeException( "Account {$id} not found" );
		}

		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			throw new \RuntimeException( "Account {$id} not found" );
		}

		return Account::fromArray( $this->dbRowToDomainArray( $row ) );
	}

	/**
	 * @return Account[]
	 */
	public function all(): array {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return [];
		}

		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );

		if ( ! is_array( $rows ) || $rows === [] ) {
			return [];
		}

		$out = [];
		foreach ( $rows as $row ) {
			$id = (int) ( $row['id'] ?? 0 );
			if ( $id > 0 ) {
				$out[] = Account::fromArray( $this->dbRowToDomainArray( $row ) );
			}
		}

		return $out;
	}

	/**
	 * @param int[] $ids
	 *
	 * @return Account[]
	 */
	public function getByIds( array $ids ): array {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return [];
		}

		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		if ( $ids === [] ) {
			return [];
		}

		$table        = $this->tableName();
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "SELECT * FROM {$table} WHERE id IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$ids ), ARRAY_A );
		if ( ! is_array( $rows ) || $rows === [] ) {
			return [];
		}

		$out = [];
		foreach ( $rows as $row ) {
			$id = (int) ( $row['id'] ?? 0 );
			if ( $id <= 0 ) {
				continue;
			}

			$out[] = Account::fromArray( $this->dbRowToDomainArray( $row ) );
		}

		return $out;
	}

	public function getCredentialsById( int $id ): AccountCredentials {
		$account = $this->get( $id );

		$igAccountId = trim( $account->igAccountId() );
		if ( $igAccountId === '' ) {
			throw new \RuntimeException( 'Account has empty igAccountId, accountId=' . $id );
		}

		$accessToken = trim( $account->accessToken() );
		if ( $accessToken === '' ) {
			throw new \RuntimeException( 'Missing or unreadable access token for accountId=' . $id . '. Reconnect the account.' );
		}

		$accountType = trim( $account->accountType() );
		if ( $accountType === '' ) {
			$accountType = 'BUSINESS';
		}

		return new AccountCredentials(
			$account->id(),
			$igAccountId,
			$accessToken,
			$accountType,
			$account->connectType()
		);
	}

	public function delete( int $id ): void {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return;
		}

		$table  = $this->tableName();
		$result = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );

		if ( $result === false ) {
			$this->logDbError( 'delete', $wpdb->last_error );
		}
	}

	private function tableName(): string {
		return $this->accountsTable->table_name();
	}

	private function ensureTable(): bool {
		return $this->accountsTable->ensureExists();
	}

	private function insert( Account $account ): int {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return 0;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		[ $data, $format ] = $this->toDbRowForInsert( $account, $now );

		$result = $wpdb->insert( $table, $data, $format );

		if ( $result === false ) {
			$this->logDbError( 'insert', $wpdb->last_error );

			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	private function update( int $id, Account $account ): int {
		global $wpdb;

		if ( ! $this->ensureTable() ) {
			return 0;
		}

		$table = $this->tableName();
		$now   = current_time( 'mysql', true );

		[ $data, $format ] = $this->toDbRowForUpdate( $account, $now );

		$result = $wpdb->update(
			$table,
			$data,
			[ 'id' => $id ],
			$format,
			[ '%d' ]
		);

		if ( $result === false ) {
			$this->logDbError( 'update', $wpdb->last_error );

			return 0;
		}

		return $id;
	}

	/**
	 * @return array{0: array<string,mixed>, 1: array<int,string>}
	 */
	private function toDbRowForInsert( Account $account, string $now ): array {
		[ $data, $format ] = $this->baseDbRow( $account );

		$data['created_at'] = $now;
		$data['updated_at'] = $now;

		$format[] = '%s';
		$format[] = '%s';

		return [ $data, $format ];
	}

	/**
	 * @return array{0: array<string,mixed>, 1: array<int,string>}
	 */
	private function toDbRowForUpdate( Account $account, string $now ): array {
		[ $data, $format ] = $this->baseDbRow( $account );

		$data['updated_at'] = $now;
		$format[]           = '%s';

		return [ $data, $format ];
	}

	/**
	 * @return array{0: array<string,mixed>, 1: array<int,string>}
	 */
	private function baseDbRow( Account $account ): array {
		$hasIvColumn = $this->hasAccessTokenIvColumn();
		if ( $hasIvColumn ) {
			[ $encryptedToken, $tokenIv ] = $this->cipher->encrypt( $account->accessToken() );
		} else {
			$encryptedToken = $account->accessToken();
			$tokenIv        = '';
		}

		$data = [
			'ig_account_id' => $account->igAccountId(),
			'account_type'  => $account->accountType(),
			'connect_type'  => $account->connectType(),
			'name'          => $account->name(),
			'username'      => $account->username(),
			'access_token'  => $encryptedToken,
		];

		if ( $hasIvColumn ) {
			$data['access_token_iv'] = $tokenIv;
		}

		$data += [
			'avatar'          => $account->avatar(),
			'biography'       => wp_encode_emoji( $account->biography() ),
			'media_count'     => $account->mediaCount(),
			'followers_count' => $account->followersCount(),
			'follows_count'   => $account->followsCount(),
			'token_expires'   => $account->tokenExpires(),
			'last_update'     => $account->lastUpdate(),
		];

		$format = [
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		];

		if ( $hasIvColumn ) {
			$format[] = '%s';
		}

		$format = array_merge(
			$format,
			[
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
			]
		);

		[ $data, $format ] = $this->appendTokenRefreshAttemptColumn( $data, $format, $account->tokenRefreshAttemptAt() );

		return [ $data, $format ];
	}

	private function dbRowToDomainArray( array $row ): array {
		$rawToken    = (string) ( $row['access_token'] ?? '' );
		$tokenIv     = (string) ( $row['access_token_iv'] ?? '' );
		$accessToken = $this->cipher->decrypt( $rawToken, $tokenIv );

		return [
			'id'                    => (string) ( $row['id'] ?? '' ),
			'igAccountId'           => (string) ( $row['ig_account_id'] ?? '' ),
			'accountType'           => (string) ( $row['account_type'] ?? '' ),
			'connectType'           => (string) ( $row['connect_type'] ?? '' ),
			'name'                  => (string) ( $row['name'] ?? '' ),
			'username'              => (string) ( $row['username'] ?? '' ),
			'accessToken'           => $accessToken,
			'avatar'                => (string) ( $row['avatar'] ?? '' ),
			'biography'             => (string) ( $row['biography'] ?? '' ),
			'mediaCount'            => (int) ( $row['media_count'] ?? 0 ),
			'followersCount'        => (int) ( $row['followers_count'] ?? 0 ),
			'followsCount'          => (int) ( $row['follows_count'] ?? 0 ),
			'tokenExpires'          => (int) ( $row['token_expires'] ?? 0 ),
			'tokenRefreshAttemptAt' => $this->readTokenRefreshAttemptAt( $row ),
			'lastUpdate'            => $row['last_update'] ?? null,
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @param string[]            $format
	 *
	 * @return array{0: array<string,mixed>, 1: array<int,string>}
	 */
	private function appendTokenRefreshAttemptColumn( array $data, array $format, int $timestamp ): array {
		if ( $this->hasTokenRefreshAttemptAtColumn() ) {
			$data['token_refresh_attempt_at'] = $this->formatAttemptAt( $timestamp );
			$format[]                         = '%s';
		} else {
			$data['token_refresh_attempt'] = $timestamp;
			$format[]                      = '%d';
		}

		return [ $data, $format ];
	}

	/**
	 * @param array<string,mixed> $row
	 */
	private function readTokenRefreshAttemptAt( array $row ): int {
		if ( $this->hasTokenRefreshAttemptAtColumn() ) {
			return $this->parseAttemptAt( (string) ( $row['token_refresh_attempt_at'] ?? '' ) );
		}

		return (int) ( $row['token_refresh_attempt'] ?? 0 );
	}

	private function formatAttemptAt( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return '';
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	private function parseAttemptAt( string $value ): int {
		$value = trim( $value );
		if ( $value === '' || $value === '0' || strpos( $value, '0000-00-00' ) === 0 ) {
			return 0;
		}

		$parsed = strtotime( $value . ' UTC' );
		if ( ! $parsed || $parsed < 0 ) {
			return 0;
		}

		return (int) $parsed;
	}

	private function hasTokenRefreshAttemptAtColumn(): bool {
		if ( $this->hasTokenRefreshAttemptAtColumn !== null ) {
			return $this->hasTokenRefreshAttemptAtColumn;
		}

		if ( ! $this->ensureTable() ) {
			$this->hasTokenRefreshAttemptAtColumn = false;

			return false;
		}

		global $wpdb;
		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'token_refresh_attempt_at' )
		);

		$this->hasTokenRefreshAttemptAtColumn = is_string( $found ) && $found !== '';

		return $this->hasTokenRefreshAttemptAtColumn;
	}

	private function hasAccessTokenIvColumn(): bool {
		if ( $this->hasAccessTokenIvColumn !== null ) {
			return $this->hasAccessTokenIvColumn;
		}

		if ( ! $this->ensureTable() ) {
			$this->hasAccessTokenIvColumn = false;

			return false;
		}

		global $wpdb;
		$table = $this->tableName();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'access_token_iv' )
		);

		$this->hasAccessTokenIvColumn = is_string( $found ) && $found !== '';

		return $this->hasAccessTokenIvColumn;
	}

	private function logDbError( string $action, string $error ): void {
		if ( $error === '' ) {
			return;
		}

		Logger::error(
			'db/accounts',
			'Accounts DB ' . $action . ' failed.',
			[
				'error' => $error,
			]
		);
	}
}
