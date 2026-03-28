<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Source\Storage\SourcesRepository;

final class MediaAccountProfileHydrator {
	private AccountRepository $accounts;
	private SourcesRepository $sources;

	public function __construct(
		AccountRepository $accounts,
		SourcesRepository $sources
	) {
		$this->accounts = $accounts;
		$this->sources  = $sources;
	}

	public function hydrate( array $items ): array {
		if ( $items === [] ) {
			return [];
		}

		$sourceKeys = $this->extractSourceKeys( $items );
		if ( $sourceKeys === [] ) {
			return $this->hydrateWithoutSources( $items );
		}

		$sourcesByKey = $this->sources->getByKeys( $sourceKeys );
		$accountsById = $this->loadAccountsById( $sourcesByKey );

		$out = [];
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sourceKey     = $this->readSourceKey( $item );
			$sourceRow     = $sourceKey !== '' && isset( $sourcesByKey[ $sourceKey ] ) ? $sourcesByKey[ $sourceKey ] : null;
			$account       = $this->resolveSourceAccount( $sourceRow, $accountsById );
			$mediaUsername = isset( $item['username'] ) ? (string) $item['username'] : '';

			$item = $this->appendAccountProfile( $item, $mediaUsername, $sourceRow, $account );
			$out[] = $item;
		}

		return $out;
	}

	private function extractSourceKeys( array $items ): array {
		$keys = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$key = $this->readSourceKey( $item );
			if ( $key !== '' ) {
				$keys[] = $key;
			}
		}

		return array_values( array_unique( $keys ) );
	}

	private function readSourceKey( array $item ): string {
		$key = '';
		if ( isset( $item['sourceKey'] ) ) {
			$key = (string) $item['sourceKey'];
		} elseif ( isset( $item['source_key'] ) ) {
			$key = (string) $item['source_key'];
		}

		return trim( $key );
	}

	private function loadAccountsById( array $sourcesByKey ): array {
		$accountIds = [];
		foreach ( $sourcesByKey as $source ) {
			$accountId = isset( $source['account_id'] ) ? (int) $source['account_id'] : 0;
			if ( $accountId > 0 ) {
				$accountIds[] = $accountId;
			}
		}

		$accountIds = array_values( array_unique( $accountIds ) );
		if ( $accountIds === [] ) {
			return [];
		}

		$accounts = $this->accounts->getByIds( $accountIds );
		if ( $accounts === [] ) {
			return [];
		}

		$out = [];
		foreach ( $accounts as $account ) {
			$out[ $account->id() ] = $account;
		}

		return $out;
	}

	private function resolveSourceAccount( ?array $sourceRow, array $accountsById ): ?Account {
		if ( ! is_array( $sourceRow ) ) {
			return null;
		}

		$accountId = isset( $sourceRow['account_id'] ) ? (int) $sourceRow['account_id'] : 0;
		if ( $accountId <= 0 || ! isset( $accountsById[ $accountId ] ) ) {
			return null;
		}

		return $accountsById[ $accountId ];
	}

	private function appendAccountProfile(
		array $item,
		string $mediaUsername,
		?array $sourceRow,
		?Account $account
	): array {
		$sourceKind          = is_array( $sourceRow ) && isset( $sourceRow['kind'] ) ? (string) $sourceRow['kind'] : '';
		$normalizedMediaUser = $this->normalizeUsername( $mediaUsername );

		if ( $sourceKind === Source::KIND_HASHTAG ) {
			$tag = $this->resolveHashtagTag( $item, $sourceRow );
			if ( $tag !== '' ) {
				$item['accountUsername']    = '#' . $tag;
				$item['accountDisplayName'] = '#' . $tag;
				$item['accountAvatarUrl']   = '';
				$item['accountProfileUrl']  = $this->buildHashtagUrl( $tag );

				return $item;
			}
		}

		$accountUsername = $normalizedMediaUser;
		$accountName     = '';
		$accountAvatar   = '';
		$accountProfile  = '';

		if ( $account !== null && $this->canUseAccountProfile( $sourceKind, $normalizedMediaUser, $account ) ) {
			$normalizedAccountUser = $this->normalizeUsername( $account->username() );
			if ( $normalizedAccountUser !== '' ) {
				$accountUsername = $normalizedAccountUser;
			}

			$accountName    = trim( $account->name() );
			$accountAvatar  = trim( $account->avatar() );
			$accountProfile = $this->buildProfileUrl( $normalizedAccountUser );
		}

		if ( $accountUsername === '' ) {
			$accountUsername = 'instagram';
		}

		if ( $accountName === '' ) {
			$accountName = $accountUsername;
		}

		if ( $accountProfile === '' ) {
			$accountProfile = $this->buildProfileUrl( $accountUsername );
		}

		$item['accountUsername']    = '@' . $accountUsername;
		$item['accountDisplayName'] = $accountName;
		$item['accountAvatarUrl']   = $accountAvatar;
		$item['accountProfileUrl']  = $accountProfile;

		return $item;
	}

	private function resolveHashtagTag( array $item, ?array $sourceRow ): string {
		$sourceKey = '';
		if ( is_array( $sourceRow ) && isset( $sourceRow['source_key'] ) ) {
			$sourceKey = trim( (string) $sourceRow['source_key'] );
		}

		if ( $sourceKey === '' ) {
			$sourceKey = $this->readSourceKey( $item );
		}

		if ( strpos( $sourceKey, 'tag:' ) === 0 ) {
			$tag = strtolower( trim( substr( $sourceKey, 4 ) ) );
			return ltrim( $tag, '#' );
		}

		return '';
	}

	private function canUseAccountProfile( string $sourceKind, string $mediaUsername, Account $account ): bool {
		if ( $sourceKind === Source::KIND_ACCOUNT ) {
			return true;
		}

		if ( $sourceKind !== Source::KIND_TAGGED ) {
			return false;
		}

		$accountUsername = $this->normalizeUsername( $account->username() );
		if ( $accountUsername === '' || $mediaUsername === '' ) {
			return false;
		}

		return strtolower( $accountUsername ) === strtolower( $mediaUsername );
	}

	private function normalizeUsername( string $value ): string {
		$value = trim( $value );
		$value = ltrim( $value, '@' );

		return $value;
	}

	private function buildProfileUrl( string $username ): string {
		$username = $this->normalizeUsername( $username );
		if ( $username === '' ) {
			return '';
		}

		return 'https://www.instagram.com/' . $username;
	}

	private function buildHashtagUrl( string $tag ): string {
		$tag = ltrim( trim( $tag ), '#' );
		if ( $tag === '' ) {
			return '';
		}

		return 'https://www.instagram.com/explore/tags/' . rawurlencode( $tag ) . '/';
	}

	private function hydrateWithoutSources( array $items ): array {
		$out = [];
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$mediaUsername = isset( $item['username'] ) ? (string) $item['username'] : '';
			$out[]         = $this->appendAccountProfile( $item, $mediaUsername, null, null );
		}

		return $out;
	}
}
