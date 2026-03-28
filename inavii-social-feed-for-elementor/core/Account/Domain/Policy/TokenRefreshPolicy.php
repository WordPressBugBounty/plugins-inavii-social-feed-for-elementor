<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain\Policy;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Domain\ConnectType;

/**
 * Policy to determine when to refresh access tokens for accounts.
 */
final class TokenRefreshPolicy {
	/**
	 * Start refreshing when token expiry is this close (in seconds).
	 * Default: 40 days before expiration.
	 */
	public const DEFAULT_REFRESH_BEFORE_EXPIRY_SECONDS = 40 * 86400;

	/**
	 * Hard safety gap between two refresh attempts for the same account.
	 * Default: 12 hours.
	 */
	public const DEFAULT_MIN_RETRY_INTERVAL_SECONDS = 12 * 3600;

	private int $refreshBeforeExpirySeconds;
	private int $minRetryIntervalSeconds;

	public function __construct(
		int $refreshBeforeExpirySeconds = 0,
		int $minRetryIntervalSeconds = self::DEFAULT_MIN_RETRY_INTERVAL_SECONDS
	) {
		$this->refreshBeforeExpirySeconds = $refreshBeforeExpirySeconds > 0 ? $refreshBeforeExpirySeconds : 0;

		$this->minRetryIntervalSeconds = $minRetryIntervalSeconds > 0
			? $minRetryIntervalSeconds
			: self::DEFAULT_MIN_RETRY_INTERVAL_SECONDS;
	}

	public function shouldRefresh( Account $account, int $now ): bool {
		if ( ! ConnectType::isInstagramAccount( $account ) ) {
			return false;
		}

		$expiresAt = $account->tokenExpires();
		if ( $expiresAt <= 0 ) {
			return false;
		}

		if ( $expiresAt - $this->resolveRefreshBeforeExpirySeconds() > $now ) {
			return false;
		}

		return $this->isAttemptAllowed( $account->tokenRefreshAttemptAt(), $now );
	}

	public function markAttempt( Account $account, int $now ): void {
		$account->markTokenRefreshAttemptAt( $now );
	}

	public function isAttemptAllowed( int $lastAttempt, int $now ): bool {
		if ( $lastAttempt <= 0 ) {
			return true;
		}

		return ( $lastAttempt + $this->minRetryIntervalSeconds ) <= $now;
	}

	private function resolveRefreshBeforeExpirySeconds(): int {
		if ( $this->refreshBeforeExpirySeconds > 0 ) {
			return $this->refreshBeforeExpirySeconds;
		}

		// How many seconds before expiry we should start refreshing.
		$value = (int) apply_filters(
			'inavii/social-feed/account/token/refresh_before_expiry_seconds',
			self::DEFAULT_REFRESH_BEFORE_EXPIRY_SECONDS
		);

		return $value > 0 ? $value : self::DEFAULT_REFRESH_BEFORE_EXPIRY_SECONDS;
	}
}
