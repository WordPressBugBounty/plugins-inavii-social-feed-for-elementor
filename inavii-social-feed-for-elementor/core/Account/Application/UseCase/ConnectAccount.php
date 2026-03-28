<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Application\UseCase;

use Inavii\Instagram\Account\Application\AvatarProcessor;
use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Domain\AccountSnapshot;
use Inavii\Instagram\Account\Domain\ConnectType;
use Inavii\Instagram\Account\Domain\Policy\ConnectAccountPolicy;
use Inavii\Instagram\Account\Fetcher\AccountFetcher;
use Inavii\Instagram\Account\Storage\AccountRepository;

final class ConnectAccount {
	private AccountFetcher $api;
	private AccountRepository $repository;
	private AvatarProcessor $avatars;
	private ConnectAccountPolicy $policy;

	public function __construct(
		AccountFetcher $api,
		AccountRepository $repository,
		AvatarProcessor $avatars,
		ConnectAccountPolicy $policy
	) {
		$this->api        = $api;
		$this->repository = $repository;
		$this->avatars    = $avatars;
		$this->policy     = $policy;
	}

	public function handle( string $accessToken, int $tokenExpires, string $businessId = '' ): Account {
		$connectType = ConnectType::resolve( $accessToken, $businessId );

		$dto = $connectType === ConnectType::FACEBOOK
			? $this->fetchFacebookAccount( $accessToken, $tokenExpires, $businessId )
			: $this->fetchInstagramAccount( $accessToken, $tokenExpires );

		$account = $this->saveSnapshot( $dto );

		/**
		 * Fires after an account is connected.
		 *
		 * @param int    $accountId
		 * @param string $igAccountId
		 * @param string $connectType
		 */
		do_action( 'inavii/social-feed/account/connected', $account->id(), $account->igAccountId(), $connectType );
		do_action( 'inavii/social-feed/front-index/rebuild', [ 'igAccountId' => $account->igAccountId() ] );

		return $account;
	}

	private function fetchFacebookAccount( string $accessToken, int $tokenExpires, string $businessId ): AccountSnapshot {
		$now          = time();
		$businessId   = $this->policy->requireBusinessId( $businessId );
		$tokenExpires = $this->policy->resolveFacebookTokenExpires( $tokenExpires, $now );
		$result       = $this->api->fetchBusiness( $businessId, $accessToken );

		return $result->toSnapshot( $accessToken, $tokenExpires, ConnectType::FACEBOOK, 'business' );
	}

	private function fetchInstagramAccount( string $accessToken, int $tokenExpires ): AccountSnapshot {
		$tokenExpires = $this->policy->resolveInstagramTokenExpires( $tokenExpires, time() );
		$result       = $this->api->fetchPersonal( $accessToken );

		return $result->toSnapshot( $accessToken, $tokenExpires, ConnectType::INSTAGRAM, 'personal' );
	}

	private function saveSnapshot( AccountSnapshot $snapshot ): Account {
		$existing = $this->repository->findByIgAccountId( $snapshot->id() );
		if ( $existing ) {
			$account = $existing;
		} else {
			$account = Account::fromSnapshot( $snapshot );
		}

		$account->applySnapshot( $snapshot );
		$account->touchLastUpdate( (string) current_time( 'mysql', true ) );

		$id    = $this->repository->save( $account );
		$saved = $this->repository->get( $id );

		$generatedAvatar = $this->avatars->generateAvatar(
			$snapshot->avatar(),
			$snapshot->id(),
			$snapshot->userName()
		);

		if ( $generatedAvatar !== '' && $generatedAvatar !== $saved->avatar() ) {
			$saved->updateAvatar( $generatedAvatar );
			$this->repository->save( $saved );
			$saved = $this->repository->get( $id );
		}

		return $saved;
	}
}
