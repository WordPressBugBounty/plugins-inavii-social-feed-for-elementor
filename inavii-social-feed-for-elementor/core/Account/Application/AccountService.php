<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Account\Application;

use Inavii\Instagram\Account\Application\UseCase\ConnectAccount;
use Inavii\Instagram\Account\Application\UseCase\DeleteAccount;
use Inavii\Instagram\Account\Application\UseCase\RefreshAccountStats;
use Inavii\Instagram\Account\Application\UseCase\RefreshAccessTokens;
use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Application\Api\AccountApiService;
use Inavii\Instagram\Account\Storage\AccountRepository;

final class AccountService {
	private AccountRepository $repository;
	private ConnectAccount $connect;
	private DeleteAccount $deleteAccount;
	private RefreshAccountStats $refreshStats;
	private RefreshAccessTokens $refreshTokens;
	private AccountApiService $api;

	public function __construct(
		AccountRepository $repository,
		ConnectAccount $connect,
		DeleteAccount $deleteAccount,
		RefreshAccountStats $refreshStats,
		RefreshAccessTokens $refreshTokens,
		AccountApiService $api
	) {
		$this->repository    = $repository;
		$this->connect       = $connect;
		$this->deleteAccount = $deleteAccount;
		$this->refreshStats  = $refreshStats;
		$this->refreshTokens = $refreshTokens;
		$this->api           = $api;
	}

	public function connect( string $accessToken, int $tokenExpires, string $businessId = '' ): Account {
		return $this->connect->handle( $accessToken, $tokenExpires, $businessId );
	}

	public function get( int $id ): Account {
		return $this->repository->get( $id );
	}

	/**
	 * @return Account[]
	 */
	public function all(): array {
		return $this->repository->all();
	}

	public function update( Account $account ): void {
		$this->repository->save( $account );
	}

	public function delete( int $accountId ): void {
		$this->deleteAccount->handle( $accountId );
	}

	public function refreshStats(): void {
		$this->refreshStats->handle();
	}

	public function refreshTokens(): void {
		$this->refreshTokens->handle();
	}

	public function api(): AccountApiService {
		return $this->api;
	}
}
