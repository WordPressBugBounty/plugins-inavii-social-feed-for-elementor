<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Application\UseCase;

use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Feed\Domain\Feed;
use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Feed\Domain\FeedSources;
use Inavii\Instagram\Feed\Domain\Policy\FeedTitleRules;
use Inavii\Instagram\Feed\Storage\FeedRepository;

final class AutoFeedTitleGenerator {
	private FeedRepository $feeds;
	private AccountRepository $accounts;
	private FeedTitleRules $rules;

	public function __construct( FeedRepository $feeds, AccountRepository $accounts, FeedTitleRules $rules ) {
		$this->feeds    = $feeds;
		$this->accounts = $accounts;
		$this->rules    = $rules;
	}

	public function resolveForCreate( string $requestedTitle, FeedSettings $settings ): string {
		$title = trim( $requestedTitle );
		if ( ! $this->rules->shouldGenerateTitle( $title ) ) {
			return $title;
		}

		return $this->makeUniqueTitle( $this->buildBaseTitle( $settings ) );
	}

	private function buildBaseTitle( FeedSettings $settings ): string {
		return $this->rules->buildBaseTitle(
			$this->resolveSourceLabel( $settings->source() ),
			$this->resolveLayoutLabel( $settings )
		);
	}

	private function resolveSourceLabel( FeedSources $sources ): string {
		$accounts = $sources->accounts();
		$tagged   = $sources->tagged();
		$hashtags = $sources->hashtags();
		$total    = count( $accounts ) + count( $tagged ) + count( $hashtags );

		if ( $total <= 0 ) {
			return 'Sources';
		}

		if ( count( $accounts ) === 1 && count( $tagged ) === 0 && count( $hashtags ) === 0 ) {
			return $this->resolveAccountLabel( (int) $accounts[0] );
		}

		if ( count( $accounts ) === 0 && count( $tagged ) === 1 && count( $hashtags ) === 0 ) {
			return 'Tagged ' . $this->resolveAccountLabel( (int) $tagged[0] );
		}

		if ( count( $accounts ) === 0 && count( $tagged ) === 0 && count( $hashtags ) === 1 ) {
			return '#' . (string) $hashtags[0];
		}

		return 'Mixed sources';
	}

	private function resolveAccountLabel( int $accountId ): string {
		if ( $accountId <= 0 ) {
			return 'Account';
		}

		try {
			$account  = $this->accounts->get( $accountId );
			$username = trim( $account->username() );
			if ( $username !== '' ) {
				return $username;
			}
		} catch ( \Throwable $e ) {
			// Fallback to a generic label when account lookup fails.
			unset( $e );
		}

		return 'Account ' . $accountId;
	}

	private function makeUniqueTitle( string $baseTitle ): string {
		$existingTitles = [];
		foreach ( $this->feeds->all() as $feed ) {
			if ( ! $feed instanceof Feed ) {
				continue;
			}

			$existingTitles[] = $feed->title();
		}

		return $this->rules->makeUniqueTitle( $baseTitle, $existingTitles );
	}

	private function resolveLayoutLabel( FeedSettings $settings ): string {
		$design = $settings->design();
		$mode   = '';

		$feedLayout = isset( $design['feedLayout'] ) && is_array( $design['feedLayout'] ) ? $design['feedLayout'] : [];
		$candidates = [
			$feedLayout['viewVariant'] ?? null,
			$feedLayout['view'] ?? null,
		];

		foreach ( $candidates as $candidate ) {
			if ( ! is_scalar( $candidate ) ) {
				continue;
			}

			$candidate = trim( (string) $candidate );
			if ( $candidate === '' ) {
				continue;
			}

			$mode = $candidate;
			break;
		}

		$mode = trim( $mode );
		if ( $mode === '' ) {
			return 'Grid';
		}

		$mode = str_replace( [ '-', '_' ], ' ', strtolower( $mode ) );
		$mode = preg_replace( '/\s+/', ' ', $mode );

		return ucwords( trim( is_string( $mode ) ? $mode : '' ) );
	}
}
