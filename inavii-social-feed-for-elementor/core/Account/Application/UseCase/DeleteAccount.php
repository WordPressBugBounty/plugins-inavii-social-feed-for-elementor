<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Application\UseCase;

use Inavii\Instagram\Account\Storage\AccountRepository;

class DeleteAccount {
	private AccountRepository $accounts;

	public function __construct(
		AccountRepository $accounts
	) {
		$this->accounts = $accounts;
	}

	public function handle( int $accountId ): void {
		if ( $accountId <= 0 ) {
			throw new \InvalidArgumentException( 'Account id must be > 0' );
		}

		$account     = $this->accounts->get( $accountId );
		$igAccountId = trim( $account->igAccountId() );
		if ( $igAccountId === '' ) {
			throw new \RuntimeException( 'Account has empty igAccountId, accountId=' . $accountId );
		}

		/**
		 * Fires before an account is deleted.
		 *
		 * @param int    $accountId
		 * @param string $igAccountId
		 * @param string $avatarUrl
		 */
		do_action( 'inavii/social-feed/account/deleted', $accountId, $igAccountId, $account->avatar() );
		do_action( 'inavii/social-feed/front-index/rebuild', [ 'igAccountId' => $igAccountId ] );

		$this->accounts->delete( $accountId );
	}
}
