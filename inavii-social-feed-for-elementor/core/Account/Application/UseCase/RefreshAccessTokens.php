<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Application\UseCase;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Domain\TokenExpiry;
use Inavii\Instagram\Account\Domain\Policy\TokenErrorClassifier;
use Inavii\Instagram\Account\Domain\Policy\TokenRefreshPolicy;
use Inavii\Instagram\Account\Fetcher\TokenFetcher;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\InstagramApi\InstagramApiException;
use Inavii\Instagram\Logger\Logger;
use Inavii\Instagram\Media\Application\MediaSourceService;
use Inavii\Instagram\Media\Source\Domain\Source;

final class RefreshAccessTokens {
	private AccountRepository $accounts;
	private MediaSourceService $sources;
	private TokenFetcher $tokens;
	private TokenErrorClassifier $errors;

	public function __construct(
		AccountRepository $accounts,
		MediaSourceService $sources,
		TokenFetcher $tokens,
		TokenErrorClassifier $errors
	) {
		$this->accounts = $accounts;
		$this->sources  = $sources;
		$this->tokens   = $tokens;
		$this->errors   = $errors;
	}

	public function handle(): void {
		$now    = time();
		$policy = new TokenRefreshPolicy();

		foreach ( $this->accounts->all() as $account ) {
			if ( ! $policy->shouldRefresh( $account, $now ) ) {
				continue;
			}

			$policy->markAttempt( $account, $now );

			try {
				$result = $this->tokens->refreshInstagramToken( $account->accessToken() );
				if ( ! $result->hasToken() ) {
					$this->accounts->save( $account );
					continue;
				}

				$account->updateAuth(
					$account->accountType(),
					$result->accessToken(),
					TokenExpiry::normalize( $result->expiresIn(), $now ),
					$account->connectType()
				);
				$this->accounts->save( $account );
			} catch ( InstagramApiException $e ) {
				$this->accounts->save( $account );
				if ( $this->errors->isTokenError(
					(int) $e->getCode(),
					(int) $e->subcode(),
					(string) $e->type(),
					(string) $e->getMessage()
				) ) {
					$this->markSourceDisabled( $account, $e->getMessage() );
				}
			}
		}
	}

	private function markSourceDisabled( Account $account, string $error ): void {
		$sourceKey = Source::accountSourceKey( $account->igAccountId() );
		$id        = $this->sources->markAuthFailureByKey( $sourceKey, $error );
		if ( $id <= 0 ) {
			return;
		}

		Logger::error(
			'account/token',
			'Auth failure while refreshing token: ' . $error,
			[
				'source_id'     => $id,
				'source_key'    => $sourceKey,
				'account_id'    => $account->id(),
				'ig_account_id' => $account->igAccountId(),
			]
		);
	}
}
