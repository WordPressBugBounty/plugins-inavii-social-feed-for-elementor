<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Application;

use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Feed\Domain\Feed;
use Inavii\Instagram\Feed\Domain\FeedSources;
use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;
use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Storage\MediaRepository;
use Inavii\Instagram\Media\Source\Domain\HashtagCleanupPolicy;
use Inavii\Instagram\Media\Source\Domain\SourceAccountPolicy;
use Inavii\Instagram\Media\Source\Domain\Source;

final class SyncFeedSources {
	private AccountRepository $accounts;
	private MediaRepository $media;
	private MediaFileService $files;
	private HashtagCleanupPolicy $hashtagCleanup;
	private SourceAccountPolicy $accountPolicy;
	private ProFeaturesPolicy $proFeatures;

	public function __construct(
		AccountRepository $accounts,
		MediaRepository $media,
		MediaFileService $files,
		HashtagCleanupPolicy $hashtagCleanup,
		SourceAccountPolicy $accountPolicy,
		ProFeaturesPolicy $proFeatures
	) {
		$this->accounts       = $accounts;
		$this->media          = $media;
		$this->files          = $files;
		$this->hashtagCleanup = $hashtagCleanup;
		$this->accountPolicy  = $accountPolicy;
		$this->proFeatures    = $proFeatures;
	}

	public function handle( Feed $feed ): void {
		$feedId  = $feed->id();
		$sources = FeedSources::fromArray( $feed->settings()->sources()->toArray(), $this->proFeatures );

		$desiredSourceIds = [];

		foreach ( $sources->accounts() as $accountId ) {
			$desiredSourceIds[] = $this->ensureAccountSource( $accountId );
		}

		foreach ( $sources->tagged() as $accountId ) {
			$desiredSourceIds[] = $this->ensureTaggedSource( $accountId );
		}

		foreach ( $sources->hashtags() as $tag ) {
			$desiredSourceIds[] = $this->ensureHashtagSource( $tag, $sources->allAccountIds() );
		}

		$desiredSourceIds = array_values( array_unique( array_filter( array_map( 'intval', $desiredSourceIds ) ) ) );

		$existingSourceIds = $this->media->feedSources()->getSourceIdsByFeedId( $feedId );
		$existingSourceIds = array_values( array_unique( array_filter( array_map( 'intval', $existingSourceIds ) ) ) );

		$toAdd = array_diff( $desiredSourceIds, $existingSourceIds );
		foreach ( $toAdd as $sourceId ) {
			$this->media->feedSources()->add( $feedId, (int) $sourceId );
		}

		$toRemove = array_diff( $existingSourceIds, $desiredSourceIds );
		foreach ( $toRemove as $sourceId ) {
			$this->media->feedSources()->remove( $feedId, (int) $sourceId );
		}

		if ( $toRemove !== [] ) {
			$this->cleanupRemovedHashtags( $toRemove );
		}

		/**
		 * Fires after feed sources have been synchronized.
		 *
		 * @param int $feedId
		 * @param int[] $sourceIds
		 */
		do_action( 'inavii/social-feed/feed/sources/synced', $feedId, $desiredSourceIds );
	}

	private function ensureAccountSource( int $accountId ): int {
		$account = $this->accounts->get( $accountId );

		$igAccountId = $this->accountPolicy->igAccountId( $account );
		if ( $igAccountId === '' ) {
			throw new \InvalidArgumentException( 'Account has empty igAccountId: ' . $accountId );
		}

		$sourceKey = Source::accountSourceKey( $igAccountId );

		return $this->media->sources()->save(
			Source::KIND_ACCOUNT,
			$sourceKey,
			$account->id(),
			$igAccountId
		);
	}

	private function ensureTaggedSource( int $accountId ): int {
		$account = $this->accounts->get( $accountId );

		if ( ! $this->accountPolicy->isBusiness( $account ) ) {
			throw new \InvalidArgumentException( 'Tagged source requires business account: ' . $accountId );
		}

		$igAccountId = $this->accountPolicy->igAccountId( $account );
		if ( $igAccountId === '' ) {
			throw new \InvalidArgumentException( 'Account has empty igAccountId: ' . $accountId );
		}

		$sourceKey = Source::tagged( $igAccountId )->dbSourceKey();

		return $this->media->sources()->save(
			Source::KIND_TAGGED,
			$sourceKey,
			$account->id(),
			$igAccountId
		);
	}

	/**
	 * @param int[] $candidateAccountIds
	 */
	private function ensureHashtagSource( string $tag, array $candidateAccountIds ): int {
		$tag = trim( $tag );
		if ( $tag === '' ) {
			throw new \InvalidArgumentException( 'Hashtag cannot be empty.' );
		}

		$businessAccount = $this->resolveBusinessAccount( $candidateAccountIds );
		if ( $businessAccount === null ) {
			throw new \InvalidArgumentException( 'Hashtag sources require a business account.' );
		}

		$sourceKey = Source::hashtag( $tag )->dbSourceKey();

		return $this->media->sources()->save(
			Source::KIND_HASHTAG,
			$sourceKey,
			$businessAccount->id(),
			$tag
		);
	}

	/**
	 * @param int[] $candidateAccountIds
	 */
	private function resolveBusinessAccount( array $candidateAccountIds ): ?\Inavii\Instagram\Account\Domain\Account {
		foreach ( $candidateAccountIds as $candidateId ) {
			if ( $candidateId <= 0 ) {
				continue;
			}

			try {
				$account = $this->accounts->get( $candidateId );
			} catch ( \Throwable $e ) {
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

	/**
	 * @param int[] $sourceIds
	 */
	private function cleanupRemovedHashtags( array $sourceIds ): void {
		$rows = $this->media->sources()->getByIds( $sourceIds );

		foreach ( $rows as $row ) {
			$sourceId = isset( $row['id'] ) ? (int) $row['id'] : 0;
			if ( $sourceId <= 0 ) {
				continue;
			}

			$useCount = $this->media->feedSources()->countBySourceId( $sourceId );
			if ( ! $this->hashtagCleanup->canDeleteSource( $row, $useCount ) ) {
				continue;
			}

			$sourceKey = (string) $row['source_key'];
			$rows      = $this->media->posts()->getFilesBySourceKey( $sourceKey );
			$parentIds = $this->files->deletePostFilesWithChildren( $rows );
			if ( $parentIds !== [] ) {
				$this->media->files()->children->deleteByParentIds( $parentIds );
			}

			$this->media->posts()->deleteBySourceKey( $sourceKey );
			$this->media->sources()->deleteById( $sourceId );
		}
	}
}
