<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Fetcher;

use Inavii\Instagram\Account\Domain\ConnectType;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\InstagramApi\Media\MediaApi;

final class AccountPostsFetcher implements SourceFetcher {

	private AccountRepository $accounts;
	private MediaApi $mediaApi;

	public function __construct( AccountRepository $accounts, MediaApi $mediaApi ) {
		$this->accounts = $accounts;
		$this->mediaApi = $mediaApi;
	}

	public function kind(): string {
		return Source::KIND_ACCOUNT;
	}

	public function fetch( Source $source ): FetchResponse {
		if ( ! $source->isAccount() ) {
			throw new \InvalidArgumentException( 'AccountPostsFetcher expects ACCOUNT source' );
		}

		$cred = $this->accounts->getCredentialsById( $source->accountId() );

		$igAccountId = trim( (string) $cred->igAccountId );
		if ( $igAccountId === '' ) {
			throw new \RuntimeException( 'Missing igAccountId for accountId=' . $source->accountId() );
		}

		$sourceKey = Source::accountSourceKey( $igAccountId );

		$connectType = ConnectType::resolve(
			(string) $cred->accessToken,
			'',
			(string) $cred->accountType,
			(string) $cred->connectType
		);
		$raw         = $this->fetchMediaByConnectType( $connectType, (string) $cred->igAccountId, (string) $cred->accessToken );

		$items = $this->unwrapRawItems( $raw );

		return new FetchResponse( $sourceKey, $items );
	}

	/**
	 * @return array
	 */
	private function fetchMediaByConnectType( string $connectType, string $igAccountId, string $accessToken ): array {
		$connectType = strtolower( $connectType );
		if ( $connectType === ConnectType::FACEBOOK ) {
			return $this->mediaApi->fetchAccountMedia( $igAccountId, $accessToken );
		}

		return $this->mediaApi->fetchPersonalMedia( $accessToken );
	}

	/**
	 * @param mixed $raw
	 * @return array
	 */
	private function unwrapRawItems( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		if ( isset( $raw['data'] ) && is_array( $raw['data'] ) ) {
			/** @var array $items */
			$items = $raw['data'];
			return $items;
		}

		$firstKey = array_key_first( $raw );
		if ( $firstKey !== null && is_int( $firstKey ) ) {
			/** @var array $items */
			$items = $raw;
			return $items;
		}

		return [];
	}
}
