<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Application\UseCase;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Domain\Policy\TokenErrorClassifier;
use Inavii\Instagram\Account\Fetcher\AccountFetcher;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\InstagramApi\InstagramApiException;
use Inavii\Instagram\Logger\Logger;
use Inavii\Instagram\Media\Application\MediaSourceService;
use Inavii\Instagram\Media\Source\Domain\Source;

/**
 * Refreshes account stats (media/followers/follows) for all stored accounts.
 */
class RefreshAccountStats {

	private AccountRepository $accounts;
	private AccountFetcher $profiles;
	private MediaSourceService $sources;
	private TokenErrorClassifier $errors;

	public function __construct(
		AccountRepository $accounts,
		AccountFetcher $profiles,
		MediaSourceService $sources,
		TokenErrorClassifier $errors
	) {
		$this->accounts = $accounts;
		$this->profiles = $profiles;
		$this->sources  = $sources;
		$this->errors   = $errors;
	}

	/**
	 * Refresh stats for all accounts.
	 *
	 * @return int Number of successfully updated accounts.
	 */
	public function handle(): int {
		$updated = 0;

		foreach ( $this->accounts->all() as $account ) {
			if ( ! $account instanceof Account ) {
				continue;
			}

			$igAccountId = trim( $account->igAccountId() );
			if ( $igAccountId === '' ) {
				continue;
			}

			$sourceKey = Source::accountSourceKey( $igAccountId );
			if ( $this->sources->isDisabledByKey( $sourceKey ) ) {
				continue;
			}

			try {
				$result = $this->profiles->fetchForAccount( $account );

				if ( $result === null ) {
					continue;
				}

				$stats = $result->stats();

				$account->updateStats( $stats->mediaCount(), $stats->followersCount(), $stats->followsCount() );
				$account->touchLastUpdate( (string) current_time( 'mysql', true ) );

				$this->accounts->save( $account );
				do_action( 'inavii/social-feed/front-index/rebuild', [ 'igAccountId' => $igAccountId ] );
				$updated++;
			} catch ( InstagramApiException $e ) {
				if ( $this->errors->isTokenError(
					(int) $e->getCode(),
					(int) $e->subcode(),
					(string) $e->type(),
					(string) $e->getMessage()
				) ) {
					$id = $this->sources->markAuthFailureByKey( $sourceKey, $e->getMessage() );
					if ( $id > 0 ) {
						Logger::error(
							'account/stats',
							'Auth failure while refreshing stats: ' . $e->getMessage(),
							[
								'source_id'     => $id,
								'source_key'    => $sourceKey,
								'account_id'    => $account->id(),
								'ig_account_id' => $account->igAccountId(),
							]
						);
					}
				}
				continue;
			} catch ( \Throwable $e ) {
				continue;
			}
		}

		return $updated;
	}
}
