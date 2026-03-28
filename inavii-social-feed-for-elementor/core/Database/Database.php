<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database;

/**
 * Central registry for all plugin custom tables.
 */
class Database {

	/** @var TableDefinition[] */
	private array $tables;
	private DatabaseStatusStore $statusStore;

	/**
	 * @param TableDefinition[] $tables
	 */
	public function __construct( array $tables, ?DatabaseStatusStore $statusStore = null ) {
		$this->tables      = $tables;
		$this->statusStore = $statusStore instanceof DatabaseStatusStore ? $statusStore : new DatabaseStatusStore();
	}

	public function register_tables(): void {
		foreach ( $this->tables as $table ) {
			$table->register_table();
		}
	}

	public function maybe_install(): void {
		foreach ( $this->tables as $table ) {
			$table->maybe_create();
		}
	}

	public function needsInstall(): bool {
		foreach ( $this->tables as $table ) {
			if ( method_exists( $table, 'needsInstall' ) && $table->needsInstall() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function repair(): array {
		$rows = [];

		foreach ( $this->tables as $table ) {
			if ( method_exists( $table, 'register_table' ) ) {
				$table->register_table();
			}

			if ( method_exists( $table, 'repair' ) ) {
				$rows[] = $table->repair();
				continue;
			}

			$table->maybe_create();
		}

		$failed = 0;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			if ( isset( $row['status'] ) && (string) $row['status'] !== 'ok' ) {
				$failed++;
			}
		}

		return [
			'rows'    => $rows,
			'failed'  => $failed,
			'summary' => $this->statusStore->state(),
		];
	}
}
