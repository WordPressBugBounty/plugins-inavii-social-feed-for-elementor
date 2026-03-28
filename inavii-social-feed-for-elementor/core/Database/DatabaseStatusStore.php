<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database;

/**
 * Stores database diagnostics for all custom plugin tables in one option.
 */
final class DatabaseStatusStore {

	public const OPTION_KEY = 'inavii_social_feed_db_status';

	/**
	 * @param string $tableKey
	 * @param array<string,mixed> $payload
	 */
	public function setTableStatus( string $tableKey, array $payload ): void {
		$state = $this->state();
		$now   = current_time( 'mysql' );

		if ( ! isset( $state['tables'] ) || ! is_array( $state['tables'] ) ) {
			$state['tables'] = [];
		}

		$current = isset( $state['tables'][ $tableKey ] ) && is_array( $state['tables'][ $tableKey ] )
			? $state['tables'][ $tableKey ]
			: [];

		$state['tables'][ $tableKey ] = array_merge(
			$current,
			$payload,
			[
				'updated_at' => $now,
			]
		);

		$state['updated_at'] = $now;
		$state['summary']    = $this->buildSummary( $state['tables'] );

		update_option( self::OPTION_KEY, $state, false );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function state(): array {
		$state = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $state ) ) {
			return [];
		}

		return $state;
	}

	public function clear(): void {
		delete_option( self::OPTION_KEY );
	}

	/**
	 * @param array<string,mixed> $tables
	 * @return array<string,int>
	 */
	private function buildSummary( array $tables ): array {
		$summary = [
			'ok'      => 0,
			'missing' => 0,
			'error'   => 0,
			'unknown' => 0,
		];

		foreach ( $tables as $row ) {
			if ( ! is_array( $row ) ) {
				$summary['unknown']++;
				continue;
			}

			$status = isset( $row['status'] ) ? (string) $row['status'] : 'unknown';
			if ( ! isset( $summary[ $status ] ) ) {
				$summary['unknown']++;
				continue;
			}

			$summary[ $status ]++;
		}

		return $summary;
	}
}

