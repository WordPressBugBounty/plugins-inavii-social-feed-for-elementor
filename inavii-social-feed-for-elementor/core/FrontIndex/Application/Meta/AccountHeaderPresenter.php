<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

use Inavii\Instagram\Account\Domain\Account;

final class AccountHeaderPresenter {
	public function present( Account $account, string $followLabel = '' ): array {
		$username = trim( $account->username() );
		$name     = trim( $account->name() );

		$header = [
			'name'        => $name !== '' ? $name : ( $username !== '' ? $username : 'Instagram' ),
			'username'    => $username !== '' ? '@' . ltrim( $username, '@' ) : '@instagram',
			'avatarUrl'   => $account->avatar(),
			'posts'       => $account->mediaCount(),
			'followers'   => $account->followersCount(),
			'following'   => $account->followsCount(),
			'profileUrl'  => $username !== '' ? 'https://www.instagram.com/' . ltrim( $username, '@' ) : '',
			'buttonLabel' => 'Follow',
		];

		if ( $followLabel !== '' ) {
			$header['buttonLabel'] = $followLabel;
		}

		return $header;
	}
}
