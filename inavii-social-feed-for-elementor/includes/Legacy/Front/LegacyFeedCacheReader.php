<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Front;

use Inavii\Instagram\Includes\Legacy\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Includes\Legacy\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Includes\Legacy\PostTypes\Media\MediaPostType;

final class LegacyFeedCacheReader {
	private FeedPostType $feeds;
	private MediaPostType $media;
	private AccountPostType $accounts;

	public function __construct() {
		$this->feeds    = new FeedPostType();
		$this->media    = new MediaPostType();
		$this->accounts = new AccountPostType();
	}

	public function getSlice( int $feedId, int $limit, int $offset ): array {
		$all = $this->getAll( $feedId );
		if ( $all === [] ) {
			return $this->getFromLegacyMedia( $feedId, $limit, $offset );
		}

		return [
			'items' => array_slice( $all, max( 0, $offset ), max( 1, $limit ) ),
			'total' => count( $all ),
		];
	}

	public function count( int $feedId ): int {
		$all = $this->getAll( $feedId );
		if ( $all !== [] ) {
			return count( $all );
		}

		$fallback = $this->getFromLegacyMedia( $feedId, 1, 0 );
		return (int) $fallback['total'];
	}

	private function getAll( int $feedId ): array {
		if ( $feedId <= 0 ) {
			return [];
		}

		$raw = get_post_meta( $feedId, FeedPostType::META_KEY_FEEDS, true );
		if ( ! is_array( $raw ) || $raw === [] ) {
			return [];
		}

		$items = [];
		foreach ( $raw as $item ) {
			if ( is_array( $item ) ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	private function getFromLegacyMedia( int $feedId, int $limit, int $offset ): array {
		if ( $feedId <= 0 ) {
			return [
				'items' => [],
				'total' => 0,
			];
		}

		$settings = $this->feeds->getSettings( $feedId );
		$sources  = $this->resolveSources( isset( $settings['source'] ) && is_array( $settings['source'] ) ? $settings['source'] : [] );
		if ( $sources === [] ) {
			return [
				'items' => [],
				'total' => 0,
			];
		}

		$result = $this->media->getMedia( $sources, $settings, max( 1, $limit ), max( 0, $offset ) );
		return [
			'items' => $result->getPosts(),
			'total' => $result->getTotal(),
		];
	}

	private function resolveSources( array $source ): array {
		$resolved = [];

		$accounts = isset( $source['accounts'] ) && is_array( $source['accounts'] ) ? $source['accounts'] : [];
		foreach ( $accounts as $accountId ) {
			$key = $this->resolveAccountSourceKey( (int) $accountId );
			if ( $key !== '' ) {
				$resolved[] = $key;
			}
		}

		$tagged = isset( $source['tagged'] ) && is_array( $source['tagged'] ) ? $source['tagged'] : [];
		foreach ( $tagged as $accountId ) {
			$username = $this->resolveAccountUsername( (int) $accountId );
			if ( $username !== '' ) {
				$resolved[] = $username . '|TAGGED_ACCOUNT';
			}
		}

		$hashtags = isset( $source['hashtags'] ) && is_array( $source['hashtags'] ) ? $source['hashtags'] : [];
		foreach ( $hashtags as $hashtag ) {
			if ( ! is_array( $hashtag ) ) {
				continue;
			}

			$id   = isset( $hashtag['id'] ) ? (string) $hashtag['id'] : '';
			$type = isset( $hashtag['type'] ) ? (string) $hashtag['type'] : '';
			if ( $id === '' || $type === '' ) {
				continue;
			}

			$resolved[] = $id . '|' . $type;
		}

		return array_values( array_unique( $resolved ) );
	}

	private function resolveAccountSourceKey( int $accountId ): string {
		$username = $this->resolveAccountUsername( $accountId );
		if ( $username === '' ) {
			return '';
		}

		$account = $this->accounts->get( $accountId );
		$type    = strtoupper( $account->accountType() );
		if ( $type === '' ) {
			return '';
		}

		return $username . '|' . $type;
	}

	private function resolveAccountUsername( int $accountId ): string {
		if ( $accountId <= 0 ) {
			return '';
		}

		$account = $this->accounts->get( $accountId );
		$username = trim( $account->userName() );

		return $username;
	}

}
