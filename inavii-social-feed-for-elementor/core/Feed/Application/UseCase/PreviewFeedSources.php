<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Application\UseCase;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Feed\Domain\FeedMediaFilters;
use Inavii\Instagram\Feed\Domain\FeedSources;
use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;
use Inavii\Instagram\Feed\Domain\Policy\PreviewRefreshPolicy;
use Inavii\Instagram\Feed\Domain\Policy\SourceMixModePolicy;
use Inavii\Instagram\Media\Application\MediaAccountProfileHydrator;
use Inavii\Instagram\Media\Application\MediaFetchService;
use Inavii\Instagram\Media\Application\MediaPostsFinder;
use Inavii\Instagram\Media\Domain\SyncError;
use Inavii\Instagram\Media\Source\Domain\SourceAccountPolicy;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Source\Storage\SourcesRepository;

final class PreviewFeedSources {
	private AccountRepository $accounts;
	private MediaFetchService $fetch;
	private MediaPostsFinder $finder;
	private SourceAccountPolicy $accountPolicy;
	private SourcesRepository $sources;
	private PreviewRefreshPolicy $refreshPolicy;
	private SourceMixModePolicy $sourceMixMode;
	private MediaAccountProfileHydrator $profiles;
	private ProFeaturesPolicy $proFeatures;

	public function __construct(
		AccountRepository $accounts,
		MediaFetchService $fetch,
		MediaPostsFinder $finder,
		SourceAccountPolicy $accountPolicy,
		SourcesRepository $sources,
		PreviewRefreshPolicy $refreshPolicy,
		SourceMixModePolicy $sourceMixMode,
		MediaAccountProfileHydrator $profiles,
		ProFeaturesPolicy $proFeatures
	) {
		$this->accounts      = $accounts;
		$this->fetch         = $fetch;
		$this->finder        = $finder;
		$this->accountPolicy = $accountPolicy;
		$this->sources       = $sources;
		$this->refreshPolicy = $refreshPolicy;
		$this->sourceMixMode = $sourceMixMode;
		$this->profiles      = $profiles;
		$this->proFeatures   = $proFeatures;
	}

	/**
	 * @param FeedSources           $sources Selected sources for preview.
	 * @param int                   $limit Page size.
	 * @param int                   $offset Page offset.
	 * @param bool                  $refresh Whether sources should be refreshed from API.
	 * @param FeedMediaFilters|null $filters Media filters.
	 * @param int                   $feedId Feed ID (0 for not yet persisted draft).
	 *
	 * @return array
	 */
	public function handle( FeedSources $sources, int $limit, int $offset, bool $refresh, ?FeedMediaFilters $filters = null, int $feedId = 0 ): array {
		$sources    = FeedSources::fromArray( $sources->toArray(), $this->proFeatures );
		$filters    = $this->normalizeFilters( $filters );
		$resolved   = $this->resolveSources( $sources );
		$sourceKeys = $this->collectSourceKeys( $resolved );
		$sync       = $this->refreshResolvedSources( $resolved, $refresh, $sourceKeys );
		$result     = $this->queryItems( $sourceKeys, $filters, $feedId, $limit, $offset );

		return [
			'sources'    => $sources->toArray(),
			'sourceKeys' => $sourceKeys,
			'items'      => $result['items'],
			'total'      => $result['total'],
			'sync'       => $sync,
		];
	}

	private function normalizeFilters( ?FeedMediaFilters $filters ): FeedMediaFilters {
		if ( $filters instanceof FeedMediaFilters ) {
			return FeedMediaFilters::fromArray( $filters->toArray(), $this->proFeatures );
		}

		return FeedMediaFilters::fromArray( [], $this->proFeatures );
	}

	/** @return string[] */
	private function collectSourceKeys( array $resolved ): array {
		$keys = [];
		foreach ( $resolved as $row ) {
			$key = isset( $row['sourceKey'] ) ? trim( (string) $row['sourceKey'] ) : '';
			if ( $key !== '' ) {
				$keys[] = $key;
			}
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * @param string[] $sourceKeys
	 *
	 * @return array
	 */
	private function refreshResolvedSources( array $resolved, bool $refresh, array $sourceKeys ): array {
		$sync = $this->initialSyncState();
		if ( ! $refresh ) {
			return $sync;
		}

		$ttlSeconds = $this->refreshPolicy->ttlSeconds();
		$lastSync   = $sourceKeys !== [] ? $this->sources->getLastSyncAtBySourceKeys( $sourceKeys ) : [];
		$lastSeen   = $sourceKeys !== [] ? $this->finder->lastSeenAtBySourceKeys( $sourceKeys ) : [];
		$now        = time();

		foreach ( $resolved as $row ) {
			if ( ! isset( $row['source'] ) || ! $row['source'] instanceof Source ) {
				continue;
			}

			$sourceKey  = isset( $row['sourceKey'] ) ? trim( (string) $row['sourceKey'] ) : '';
			$lastSyncAt = $this->parseMysqlUtcTimestamp( (string) ( $lastSync[ $sourceKey ] ?? '' ) );
			$lastSeenAt = $this->parseMysqlUtcTimestamp( (string) ( $lastSeen[ $sourceKey ] ?? '' ) );

			if ( ! $this->refreshPolicy->shouldRefresh( $now, $ttlSeconds, $lastSyncAt, $lastSeenAt ) ) {
				continue;
			}

			$sync['sourcesTotal']++;

			try {
				$result                = $this->fetch->fetch( $row['source'] );
				$sync['sourcesOk']    += $result->sourcesOk;
				$sync['itemsFetched'] += $result->itemsFetched;
				$sync['itemsSaved']   += $result->itemsSaved;
				foreach ( $result->errors() as $error ) {
					if ( $error instanceof SyncError ) {
						$sync['errors'][] = $error->message();
					}
				}
			} catch ( \Throwable $e ) {
				$sync['errors'][] = $e->getMessage();
			}
		}

		return $sync;
	}

	/**
	 * @param string[] $sourceKeys
	 *
	 * @return array
	 */
	private function queryItems( array $sourceKeys, FeedMediaFilters $filters, int $feedId, int $limit, int $offset ): array {
		if ( $sourceKeys === [] ) {
			return [
				'items' => [],
				'total' => 0,
			];
		}

		$queryArgs                  = $filters->toQueryArgs();
		$queryArgs['sourceMixMode'] = $this->sourceMixMode->resolve( $feedId, $sourceKeys, $queryArgs );
		$items                      = $this->finder->bySourceKeysFiltered( $sourceKeys, $queryArgs, $limit, $offset );
		$items                      = $this->profiles->hydrate( $items );
		$total                      = $this->finder->countBySourceKeysFiltered( $sourceKeys, $queryArgs );

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	/** @return array */
	private function initialSyncState(): array {
		return [
			'sourcesTotal' => 0,
			'sourcesOk'    => 0,
			'itemsFetched' => 0,
			'itemsSaved'   => 0,
			'errors'       => [],
		];
	}

	/**
	 * @param FeedSources $sources Requested feed sources.
	 *
	 * @return array
	 */
	private function resolveSources( FeedSources $sources ): array {
		$resolved = [];
		$this->appendAccountSources( $resolved, $sources->accounts() );
		$this->appendTaggedSources( $resolved, $sources->tagged() );
		$this->appendHashtagSources( $resolved, $sources->hashtags(), $sources->allAccountIds() );

		return $resolved;
	}

	/** @param int[] $accountIds */
	private function appendAccountSources( array &$resolved, array $accountIds ): void {
		foreach ( $accountIds as $accountId ) {
			$account = $this->findAccount( $accountId );
			if ( $account === null ) {
				$resolved[] = [
					'sourceKey' => '',
					'error'     => 'Account not found: ' . $accountId,
				];
				continue;
			}

			$igAccountId = $this->accountPolicy->igAccountId( $account );
			if ( $igAccountId === '' ) {
				$resolved[] = [
					'sourceKey' => '',
					'error'     => 'Account has empty igAccountId: ' . $accountId,
				];
				continue;
			}

			$resolved[] = [
				'source'    => Source::account( $accountId ),
				'sourceKey' => Source::accountSourceKey( $igAccountId ),
			];
		}
	}

	/** @param int[] $taggedAccountIds */
	private function appendTaggedSources( array &$resolved, array $taggedAccountIds ): void {
		foreach ( $taggedAccountIds as $accountId ) {
			$account = $this->findAccount( $accountId );
			if ( $account === null ) {
				$resolved[] = [
					'sourceKey' => '',
					'error'     => 'Tagged account not found: ' . $accountId,
				];
				continue;
			}

			if ( ! $this->accountPolicy->isBusiness( $account ) ) {
				$resolved[] = [
					'sourceKey' => '',
					'error'     => 'Tagged source requires business account: ' . $accountId,
				];
				continue;
			}

			$igAccountId = $this->accountPolicy->igAccountId( $account );
			if ( $igAccountId === '' ) {
				$resolved[] = [
					'sourceKey' => '',
					'error'     => 'Tagged account has empty igAccountId: ' . $accountId,
				];
				continue;
			}

			$resolved[] = [
				'source'    => Source::tagged( $igAccountId, $accountId ),
				'sourceKey' => Source::tagged( $igAccountId )->dbSourceKey(),
			];
		}
	}

	/**
	 * @param string[] $hashtags
	 * @param int[]    $candidateAccountIds
	 */
	private function appendHashtagSources( array &$resolved, array $hashtags, array $candidateAccountIds ): void {
		$businessAccount   = $this->resolveBusinessAccount( $candidateAccountIds );
		$businessAccountId = $businessAccount ? $businessAccount->id() : 0;

		foreach ( $hashtags as $tag ) {
			$sourceKey = Source::hashtag( $tag )->dbSourceKey();
			$source    = Source::hashtag( $tag, $businessAccountId );

			$resolved[] = [
				'source'    => $source,
				'sourceKey' => $sourceKey,
			];
		}
	}

	private function findAccount( int $accountId ): ?Account {
		if ( $accountId <= 0 ) {
			return null;
		}

		try {
			return $this->accounts->get( $accountId );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * @param int[] $candidateAccountIds Candidate account IDs.
	 */
	private function resolveBusinessAccount( array $candidateAccountIds ): ?Account {
		foreach ( $candidateAccountIds as $candidateId ) {
			$account = $this->findAccount( $candidateId );
			if ( $account === null ) {
				continue;
			}

			if ( ! $this->accountPolicy->canUseForTaggedSource( $account ) ) {
				continue;
			}

			return $account;
		}

		$fallback = $this->accounts->findBusinessAccount();
		if ( $fallback !== null && $this->accountPolicy->canUseForTaggedSource( $fallback ) ) {
			return $fallback;
		}

		return null;
	}

	private function parseMysqlUtcTimestamp( string $value ): int {
		$value = trim( $value );
		if ( $value === '' ) {
			return 0;
		}

		$date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $value, new \DateTimeZone( 'UTC' ) );
		if ( ! $date instanceof \DateTime ) {
			return 0;
		}

		return $date->getTimestamp();
	}
}
