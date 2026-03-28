<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Storage\AccountRepository;

final class AccountCandidateResolver {
	private AccountRepository $accounts;

	public function __construct( AccountRepository $accounts ) {
		$this->accounts = $accounts;
	}

	/**
	 * @param int[] $candidateAccountIds
	 */
	public function resolveFirst( array $candidateAccountIds ): ?Account {
		foreach ( $candidateAccountIds as $accountId ) {
			try {
				return $this->accounts->get( (int) $accountId );
			} catch ( \Throwable $e ) {
				continue;
			}
		}

		return null;
	}
}
