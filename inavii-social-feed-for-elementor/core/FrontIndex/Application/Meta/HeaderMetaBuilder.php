<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Meta;

use Inavii\Instagram\FrontIndex\Domain\Policy\AccountSelectionPolicy;

final class HeaderMetaBuilder {
	private AccountCandidateResolver $accounts;
	private AccountSelectionPolicy $accountSelectionPolicy;
	private FeedSourcesExtractor $feedSources;
	private HashtagMetaPresenter $hashtags;
	private HeaderDisplayOptionsResolver $headerDisplay;
	private AccountHeaderPresenter $accountHeader;

	public function __construct(
		AccountCandidateResolver $accounts,
		AccountSelectionPolicy $accountSelectionPolicy,
		FeedSourcesExtractor $feedSources,
		HashtagMetaPresenter $hashtags,
		HeaderDisplayOptionsResolver $headerDisplay,
		AccountHeaderPresenter $accountHeader
	) {
		$this->accounts               = $accounts;
		$this->accountSelectionPolicy = $accountSelectionPolicy;
		$this->feedSources            = $feedSources;
		$this->hashtags               = $hashtags;
		$this->headerDisplay          = $headerDisplay;
		$this->accountHeader          = $accountHeader;
	}

	/**
	 * @param array $feed
	 * @param array $design
	 */
	public function build( array $feed, array $design ): array {
		if ( $this->headerDisplay->isDisabled( $design ) ) {
			return [ 'showHeader' => false ];
		}

		$headerElements = isset( $design['header'] ) && is_array( $design['header'] ) ? $design['header'] : [];
		$buttonSettings = isset( $design['buttonSettings'] ) && is_array( $design['buttonSettings'] ) ? $design['buttonSettings'] : [];
		$header         = $this->resolveHeader( $feed, $headerElements, $buttonSettings );

		return $header !== null ? [ 'header' => $header ] : [];
	}

	/**
	 * @param array $feed
	 * @param array $elements
	 * @param array $buttonSettings
	 */
	private function resolveHeader( array $feed, array $elements, array $buttonSettings ): ?array {
		$sources     = $this->feedSources->extractSources( $feed );
		$accountIds  = $this->accountSelectionPolicy->candidateAccountIds( $sources );
		$hashtags    = $this->feedSources->extractHashtags( $sources );
		$visibility  = $this->headerDisplay->normalizeVisibility( $elements );
		$followLabel = $this->headerDisplay->normalizeFollowLabel( $buttonSettings );

		$account = $this->accounts->resolveFirst( $accountIds );
		if ( $account !== null ) {
			$header = $this->accountHeader->present( $account, $followLabel );

			if ( $visibility !== [] ) {
				$header = array_merge( $header, $visibility );
			}

			return $header;
		}

		$header = $this->hashtags->buildHeaderFallback( $hashtags, $followLabel );
		if ( $header === null ) {
			return null;
		}

		$header = $this->headerDisplay->applyHashtagOverrides( $header );
		if ( $visibility !== [] ) {
			$header = array_merge( $header, $visibility );
		}

		return $header;
	}
}
