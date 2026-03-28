<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database\Tables;

use Inavii\Instagram\Database\DatabaseStatusStore;
use Inavii\Instagram\Database\TableDefinition;
use Inavii\Instagram\Logger\Logger;

abstract class AbstractTable implements TableDefinition {
	/** @var array */
	private static array $existsCache = [];
	private ?DatabaseStatusStore $statusStore;

	public function __construct( ?DatabaseStatusStore $statusStore = null ) {
		$this->statusStore = $statusStore;
	}

	public function register_table(): void {
		global $wpdb;
		$wpdb->{$this->wpdbProp()} = $this->table_name();
	}

	public function maybe_create(): void {
		$tableName        = $this->table_name();
		$tableKey         = $this->statusKey();
		$installedVersion = (int) get_option( $this->optDbVersion(), 0 );

		// If table doesn't exist, try to create it.
		if ( ! $this->table_exists() ) {
			$createResult = $this->create_or_upgrade();

			if ( $this->refreshTableExists() ) {
				$this->markInstalled( true );
				$this->storeStatus(
					$tableKey,
					[
						'status'            => 'ok',
						'table'             => $tableName,
						'db_version'        => $this->dbVersion(),
						'installed_version' => $this->dbVersion(),
						'exists'            => true,
						'action'            => 'create',
						'last_error'        => '',
						'last_query'        => '',
					]
				);
				return;
			}

			$this->markFailed( $createResult['last_error'], $createResult['last_query'] );
			$this->storeStatus(
				$tableKey,
				[
					'status'            => 'error',
					'table'             => $tableName,
					'db_version'        => $this->dbVersion(),
					'installed_version' => $installedVersion,
					'exists'            => false,
					'action'            => 'create',
					'last_error'        => $createResult['last_error'],
					'last_query'        => $createResult['last_query'],
				]
			);
			return;
		}

		// Table exists, run dbDelta only when version differs (future migrations).
		if ( $installedVersion !== $this->dbVersion() ) {
			$createResult = $this->create_or_upgrade();

			if ( $this->refreshTableExists() ) {
				$this->markInstalled( true );
				$this->storeStatus(
					$tableKey,
					[
						'status'            => 'ok',
						'table'             => $tableName,
						'db_version'        => $this->dbVersion(),
						'installed_version' => $this->dbVersion(),
						'exists'            => true,
						'action'            => 'upgrade',
						'last_error'        => '',
						'last_query'        => '',
					]
				);
				return;
			}

			$this->markFailed( $createResult['last_error'], $createResult['last_query'] );
			$this->storeStatus(
				$tableKey,
				[
					'status'            => 'error',
					'table'             => $tableName,
					'db_version'        => $this->dbVersion(),
					'installed_version' => $installedVersion,
					'exists'            => false,
					'action'            => 'upgrade',
					'last_error'        => $createResult['last_error'],
					'last_query'        => $createResult['last_query'],
				]
			);
			return;
		}

		// Everything OK.
		$this->markInstalled( false );
		$this->storeStatus(
			$tableKey,
			[
				'status'            => 'ok',
				'table'             => $tableName,
				'db_version'        => $this->dbVersion(),
				'installed_version' => $installedVersion,
				'exists'            => true,
				'action'            => 'noop',
			]
		);
	}

	/**
	 * Force a table repair attempt and return the latest row status.
	 *
	 * @return array<string,mixed>
	 */
	public function repair(): array {
		$tableKey         = $this->statusKey();
		$tableName        = $this->table_name();
		$installedVersion = (int) get_option( $this->optDbVersion(), 0 );
		$failedAt         = (int) get_option( $this->optDbFailed(), 0 );
		$needsRepair      = ! $this->table_exists()
			|| $installedVersion !== $this->dbVersion()
			|| $failedAt > 0;

		if ( ! $needsRepair ) {
			$row = [
				'status'            => 'ok',
				'table'             => $tableName,
				'db_version'        => $this->dbVersion(),
				'installed_version' => $installedVersion,
				'exists'            => true,
				'action'            => 'repair-noop',
				'last_error'        => '',
				'last_query'        => '',
			];
			$this->storeStatus( $tableKey, $row );
			return $row;
		}

		$createResult = $this->create_or_upgrade();
		$exists       = $this->refreshTableExists();

		if ( $exists ) {
			$this->markInstalled( true );
			$row = [
				'status'            => 'ok',
				'table'             => $tableName,
				'db_version'        => $this->dbVersion(),
				'installed_version' => $this->dbVersion(),
				'exists'            => true,
				'action'            => 'repair',
				'last_error'        => '',
				'last_query'        => '',
			];
			$this->storeStatus( $tableKey, $row );
			return $row;
		}

		$this->markFailed( $createResult['last_error'], $createResult['last_query'] );
		$row = [
			'status'            => 'error',
			'table'             => $tableName,
			'db_version'        => $this->dbVersion(),
			'installed_version' => $installedVersion,
			'exists'            => false,
			'action'            => 'repair',
			'last_error'        => $createResult['last_error'],
			'last_query'        => $createResult['last_query'],
		];
		$this->storeStatus( $tableKey, $row );
		return $row;
	}

	public function needsInstall(): bool {
		$installed = (int) get_option( $this->optDbVersion(), 0 );
		if ( $installed !== $this->dbVersion() ) {
			return true;
		}

		$failed = (int) get_option( $this->optDbFailed(), 0 );
		return $failed > 0;
	}

	public function exists(): bool {
		return $this->table_exists();
	}

	public function ensureExists(): bool {
		$this->maybe_create();
		return $this->table_exists();
	}

	public function statusKey(): string {
		return $this->baseName();
	}

	public function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . $this->baseName();
	}

	abstract protected function baseName(): string;
	abstract protected function wpdbProp(): string;
	abstract protected function dbVersion(): int;
	abstract protected function optDbVersion(): string;
	abstract protected function optDbFailed(): string;

	/**
	 * Must return full CREATE TABLE statement (dbDelta-compatible).
	 */
	abstract protected function schemaSql( string $table, string $collate ): string;

	/**
	 * @return array{last_error:string,last_query:string}
	 */
	protected function create_or_upgrade(): array {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		global $wpdb;
		$sql = $this->schemaSql( $this->table_name(), $wpdb->get_charset_collate() );
		dbDelta( $sql );

		return [
			'last_error' => isset( $wpdb->last_error ) ? (string) $wpdb->last_error : '',
			'last_query' => isset( $wpdb->last_query ) ? (string) $wpdb->last_query : '',
		];
	}

	protected function table_exists(): bool {
		global $wpdb;
		$table = $this->table_name();

		if ( isset( self::$existsCache[ $table ] ) ) {
			return self::$existsCache[ $table ];
		}

		$installedVersion = (int) get_option( $this->optDbVersion(), 0 );
		$failed = (int) get_option( $this->optDbFailed(), 0 );
		if ( $installedVersion === $this->dbVersion() && $failed === 0 && ! $this->shouldCheckFailedFlag() ) {
			self::$existsCache[ $table ] = true;
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		$exists                      = is_string( $found ) && $found === $table;
		self::$existsCache[ $table ] = $exists;

		return $exists;
	}

	protected function markInstalled( bool $updateVersion ): void {
		delete_option( $this->optDbFailed() );

		if ( $updateVersion ) {
			update_option( $this->optDbVersion(), $this->dbVersion(), true );
		}
		self::$existsCache[ $this->table_name() ] = true;
	}

	protected function markFailed( string $error = '', string $query = '' ): void {
		update_option( $this->optDbFailed(), time(), false );
		self::$existsCache[ $this->table_name() ] = false;

		Logger::error(
			'database',
			'Database table create/upgrade failed.',
			[
				'table' => $this->table_name(),
				'error' => $error,
				'query' => $query,
			]
		);
	}

	private function refreshTableExists(): bool {
		unset( self::$existsCache[ $this->table_name() ] );
		return $this->table_exists();
	}

	/**
	 * @param string $tableKey
	 * @param array<string,mixed> $row
	 */
	private function storeStatus( string $tableKey, array $row ): void {
		if ( ! $this->statusStore instanceof DatabaseStatusStore ) {
			return;
		}

		$this->statusStore->setTableStatus( $tableKey, $row );
	}

	private function shouldCheckFailedFlag(): bool {
		return is_admin()
			|| wp_doing_cron()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}
