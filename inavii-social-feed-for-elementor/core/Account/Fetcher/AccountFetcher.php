<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Fetcher;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Domain\ConnectType;
use Inavii\Instagram\Account\Dto\AccountFetchResult;
use Inavii\Instagram\InstagramApi\Account\AccountApi;

final class AccountFetcher {
	private AccountApi $api;

	public function __construct( AccountApi $api ) {
		$this->api = $api;
	}

	public function fetchPersonal( string $accessToken ): AccountFetchResult {
		return new AccountFetchResult( $this->api->fetchPersonal( $accessToken ) );
	}

	public function fetchBusiness( string $igAccountId, string $accessToken ): AccountFetchResult {
		return new AccountFetchResult( $this->api->fetchBusiness( $igAccountId, $accessToken ) );
	}

	public function fetchForAccount( Account $account ): ?AccountFetchResult {
		$token = $account->accessToken();
		if ( $token === '' ) {
			return null;
		}

		$connectType = ConnectType::resolve( $token, '', $account->accountType(), $account->connectType() );

		if ( $connectType === ConnectType::FACEBOOK ) {
			$id = $account->igAccountId();

			if ( $id === '' ) {
				return null;
			}

			return $this->fetchBusiness( $id, $token );
		}

		return $this->fetchPersonal( $token );
	}
}
