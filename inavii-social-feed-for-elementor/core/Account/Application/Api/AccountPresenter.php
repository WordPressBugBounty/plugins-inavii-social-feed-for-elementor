<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Application\Api;

use Inavii\Instagram\Account\Domain\Account;

final class AccountPresenter {
	/**
	 * @param Account $account
	 *
	 * @return array
	 */
	public function forApi( Account $account ): array {
		$bio = $account->biography();
		if ( $bio !== '' ) {
			$decoded = html_entity_decode( $bio, ENT_QUOTES, 'UTF-8' );
			$bio     = $decoded !== '' ? $decoded : $bio;
		}

		return [
			'id'             => $account->id(),
			'igAccountId'    => $account->igAccountId(),
			'accountType'    => $account->accountType(),
			'connectType'    => $account->connectType(),
			'name'           => $account->name(),
			'username'       => $account->username(),
			'avatar'         => $account->avatar(),
			'biography'      => $bio,
			'mediaCount'     => $account->mediaCount(),
			'followersCount' => $account->followersCount(),
			'followsCount'   => $account->followsCount(),
			'tokenExpires'   => $account->tokenExpires(),
			'lastUpdate'     => $account->lastUpdate() ?? '',
		];
	}
}
