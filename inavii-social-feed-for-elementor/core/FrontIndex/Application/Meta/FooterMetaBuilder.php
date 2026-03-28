<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

use Inavii\Instagram\FrontIndex\Domain\Policy\AccountSelectionPolicy;

final class FooterMetaBuilder {
	private AccountCandidateResolver $accounts;
	private FeedSourcesExtractor $feedSources;
	private AccountSelectionPolicy $accountSelectionPolicy;
	private HashtagMetaPresenter $hashtags;
	private FooterDisplayOptionsResolver $footerDisplay;

	public function __construct(
		AccountCandidateResolver $accounts,
		FeedSourcesExtractor $feedSources,
		AccountSelectionPolicy $accountSelectionPolicy,
		HashtagMetaPresenter $hashtags,
		FooterDisplayOptionsResolver $footerDisplay
	) {
		$this->accounts               = $accounts;
		$this->feedSources            = $feedSources;
		$this->accountSelectionPolicy = $accountSelectionPolicy;
		$this->hashtags               = $hashtags;
		$this->footerDisplay          = $footerDisplay;
	}

	/**
	 * @param array $feed
	 * @param array $design
	 */
	public function build( array $feed, array $design ): array {
		if ( $this->footerDisplay->isDisabled( $design ) ) {
			return [ 'showFooter' => false ];
		}

		$footerElements = isset( $design['footer'] ) && is_array( $design['footer'] ) ? $design['footer'] : [];
		$buttonSettings = isset( $design['buttonSettings'] ) && is_array( $design['buttonSettings'] ) ? $design['buttonSettings'] : [];
		$footer         = $this->resolveFooter( $feed, $footerElements, $buttonSettings );

		if ( $footer === null ) {
			return [];
		}

		if ( $this->footerDisplay->isFullyDisabled( $footer ) ) {
			return [ 'showFooter' => false ];
		}

		return [ 'footer' => $footer ];
	}

	/**
	 * @param array $feed
	 * @param array $elements
	 * @param array $buttonSettings
	 */
	private function resolveFooter( array $feed, array $elements, array $buttonSettings ): ?array {
		$footer = [];

		$visibility = $this->footerDisplay->normalizeVisibility( $elements );
		if ( $visibility !== [] ) {
			$footer = array_merge( $footer, $visibility );
		}

		$labels = $this->footerDisplay->normalizeLabels( $buttonSettings );
		if ( $labels !== [] ) {
			$footer = array_merge( $footer, $labels );
		}

		$followUrl = $this->resolveProfileUrl( $feed );
		if ( $followUrl !== '' ) {
			$footer['followUrl'] = $followUrl;
		}

		if ( $footer === [] ) {
			return null;
		}

		return $footer;
	}

	/**
	 * @param array $feed
	 */
	private function resolveProfileUrl( array $feed ): string {
		$sources    = $this->feedSources->extractSources( $feed );
		$accountIds = $this->accountSelectionPolicy->candidateAccountIds( $sources );
		$account    = $this->accounts->resolveFirst( $accountIds );

		if ( $account !== null ) {
			$username = trim( $account->username() );
			if ( $username !== '' ) {
				return 'https://www.instagram.com/' . ltrim( $username, '@' );
			}
		}

		$hashtags = $this->feedSources->extractHashtags( $sources );
		if ( $hashtags !== [] ) {
			return $this->hashtags->buildProfileUrl( $hashtags[0] );
		}

		return '';
	}
}
