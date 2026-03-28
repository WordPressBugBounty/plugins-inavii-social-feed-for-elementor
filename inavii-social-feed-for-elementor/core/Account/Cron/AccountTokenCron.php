<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Cron;

use Inavii\Instagram\Account\Application\AccountService;
use Inavii\Instagram\Cron\Lock;
use Inavii\Instagram\Logger\Logger;

final class AccountTokenCron {
	public const HOOK                = 'inavii/social-feed/account/token/refresh';
	public const LAST_RUN_OPTION     = 'inavii_account_token_refresh_last_run';
	public const LAST_SUCCESS_OPTION = 'inavii_account_token_refresh_last_success';

	private const LOCK_TTL_SECONDS = 600;

	private AccountService $accounts;

	public function __construct( AccountService $accounts ) {
		$this->accounts = $accounts;
	}

	public function register(): void {
		add_action( self::HOOK, [ $this, 'run' ] );
	}

	public function run(): void {
		$lock = new Lock( 'inavii_account_token_refresh', self::LOCK_TTL_SECONDS );

		if ( ! $lock->lock() ) {
			return;
		}

		try {
			$this->markRun();
			try {
				$this->accounts->refreshTokens();
				$this->markSuccess();
			} catch ( \Throwable $e ) {
				Logger::error(
					'cron/account_token',
					'Account token refresh failed.',
					[
						'error' => $e->getMessage(),
					]
				);
			}
		} finally {
			$lock->unlock();
		}
	}

	private function markRun(): void {
		update_option( self::LAST_RUN_OPTION, time(), false );
	}

	private function markSuccess(): void {
		update_option( self::LAST_SUCCESS_OPTION, time(), false );
	}
}
