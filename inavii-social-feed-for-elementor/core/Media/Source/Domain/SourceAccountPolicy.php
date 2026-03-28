<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Domain;

use Inavii\Instagram\Account\Domain\Account;

final class SourceAccountPolicy {
	public function isBusiness( Account $account ): bool {
		$type = strtolower( trim( $account->accountType() ) );

		return $type === 'business' || $type === 'business_basic';
	}

	public function hasIgAccountId( Account $account ): bool {
		return $account->hasIgAccountId();
	}

	public function hasAccessToken( Account $account ): bool {
		return $account->hasAccessToken();
	}

	public function canUseForTaggedSource( Account $account ): bool {
		return $this->isBusiness( $account ) && $this->hasIgAccountId( $account );
	}

	public function canUseForHashtagFetch( Account $account, bool $isAccountSourceDisabled ): bool {
		if ( $isAccountSourceDisabled ) {
			return false;
		}

		return $this->canUseForTaggedSource( $account ) && $this->hasAccessToken( $account );
	}

	public function igAccountId( Account $account ): string {
		return trim( $account->igAccountId() );
	}

	public function accessToken( Account $account ): string {
		return trim( $account->accessToken() );
	}
}
