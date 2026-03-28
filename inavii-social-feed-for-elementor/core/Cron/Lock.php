<?php
declare(strict_types=1);

namespace Inavii\Instagram\Cron;

/**
 * Transient-based lock to prevent concurrent runs.
 */
final class Lock {

	private const PREFIX = 'inavii_lock_';

	private string $transientKey;
	private int $ttlSeconds;

	public function __construct( string $name, int $ttlSeconds = 600 ) {
		$this->transientKey = self::PREFIX . $this->normalizeName( $name );
		$this->ttlSeconds   = max( 30, $ttlSeconds );
	}

	/**
	 * Returns true if lock was acquired, false if already locked.
	 */
	public function lock(): bool {
		if ( get_transient( $this->transientKey ) !== false ) {
			return false;
		}

		return (bool) set_transient( $this->transientKey, '1', $this->ttlSeconds );
	}

	public function unlock(): void {
		delete_transient( $this->transientKey );
	}

	private function normalizeName( string $name ): string {
		$name = trim( $name );
		if ( $name === '' ) {
			$name = 'default';
		}

		return sanitize_key( $name );
	}
}
