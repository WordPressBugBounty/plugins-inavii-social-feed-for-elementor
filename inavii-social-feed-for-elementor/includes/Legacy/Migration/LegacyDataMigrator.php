<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Migration;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Account\Token\AccessTokenCipher;
use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Cron\Lock;
use Inavii\Instagram\Database\Tables\AccountsTable;
use Inavii\Instagram\Feed\Domain\FeedSettings;
use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\FrontIndex\Application\FrontIndexService;
use Inavii\Instagram\Logger\Logger;
use Inavii\Instagram\Media\Source\Application\SyncFeedSources;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Source\Storage\SourcesRepository;
use Inavii\Instagram\Media\Storage\MediaPostsRepository;
use Inavii\Instagram\Includes\Legacy\PostTypes\Account\AccountPostType as LegacyAccountPostType;
use Inavii\Instagram\Includes\Legacy\PostTypes\Media\MediaPostType as LegacyMediaPostType;
use Inavii\Instagram\Includes\Legacy\RestApi\Mapper\LegacySettingsToV3Mapper;

final class LegacyDataMigrator {
	private const LOCK_NAME             = 'legacy_data_migration';
	private const LOCK_TTL_SECONDS      = 120;
	private const LEGACY_MEDIA_BATCH    = 150;
	private const LEGACY_ACCOUNT_CPT    = 'inavii_account';
	private const LEGACY_MEDIA_CPT      = 'inavii_ig_media';
	private const OPT_DONE              = 'inavii_social_feed_legacy_migration_done';
	private const OPT_ACCOUNTS_DONE     = 'inavii_social_feed_legacy_migration_accounts_done';
	private const OPT_FEED_SETTINGS_DONE = 'inavii_social_feed_legacy_migration_feed_settings_done';
	private const OPT_FEED_FILTERS_DONE = 'inavii_social_feed_legacy_migration_feed_filters_done';
	private const OPT_FEED_SOURCES_DONE = 'inavii_social_feed_legacy_migration_feed_sources_done';
	private const OPT_MEDIA_DONE        = 'inavii_social_feed_legacy_migration_media_done';
	private const OPT_MEDIA_CURSOR      = 'inavii_social_feed_legacy_migration_media_cursor';
	private const OPT_INDEX_DONE        = 'inavii_social_feed_legacy_migration_index_done';
	private const OPT_COMPLETED_AT      = 'inavii_social_feed_legacy_migration_completed_at';
	private const OPT_LAST_ERROR        = 'inavii_social_feed_legacy_migration_last_error';
	private const LEGACY_SOURCE_TAGGED  = 'tagged_account';
	private const LEGACY_SOURCE_TOP     = 'top_media';
	private const LEGACY_SOURCE_RECENT  = 'recent_media';
	private const DEFAULT_ACCOUNT_TYPE  = 'business';
	private const DEFAULT_CONNECT_TYPE  = 'facebook';

	private AccountRepository $accounts;
	private AccountsTable $accountsTable;
	private AccessTokenCipher $cipher;
	private FeedRepository $feeds;
	private SyncFeedSources $syncFeedSources;
	private SourcesRepository $sources;
	private MediaPostsRepository $mediaPosts;
	private FrontIndexService $frontIndex;
	private LegacySettingsToV3Mapper $legacySettingsMapper;

	/** @var array<int,Account>|null */
	private ?array $accountsById = null;
	/** @var array<string,Account>|null */
	private ?array $accountsByUsername = null;
	/** @var array<string,Account>|null */
	private ?array $accountsByIgId = null;

	public function __construct(
		AccountRepository $accounts,
		AccountsTable $accountsTable,
		AccessTokenCipher $cipher,
		FeedRepository $feeds,
		SyncFeedSources $syncFeedSources,
		SourcesRepository $sources,
		MediaPostsRepository $mediaPosts,
		FrontIndexService $frontIndex,
		LegacySettingsToV3Mapper $legacySettingsMapper
	) {
		$this->accounts        = $accounts;
		$this->accountsTable   = $accountsTable;
		$this->cipher          = $cipher;
		$this->feeds           = $feeds;
		$this->syncFeedSources = $syncFeedSources;
		$this->sources         = $sources;
		$this->mediaPosts      = $mediaPosts;
		$this->frontIndex      = $frontIndex;
		$this->legacySettingsMapper = $legacySettingsMapper;
	}

	public function maybeRunFull(): void {
		if ( $this->isFullDone() ) {
			return;
		}

		$lock = new Lock( self::LOCK_NAME, self::LOCK_TTL_SECONDS );
		if ( ! $lock->lock() ) {
			return;
		}

		try {
			$this->runAccountsStep();
			$this->runFeedSettingsStep();
			$this->runFeedSourcesStep();
			$mediaDone = $this->runMediaStep();

			if ( ! $mediaDone ) {
				return;
			}

			$this->runFrontIndexStep();
			$this->markDone();
		} catch ( \Throwable $e ) {
			update_option( self::OPT_LAST_ERROR, $e->getMessage(), false );
			Logger::error(
				'legacy/migration',
				'Legacy migration failed.',
				[
					'error' => $e->getMessage(),
				]
			);
		} finally {
			$lock->unlock();
		}
	}

	public function maybeRunCritical(): void {
		if ( $this->isCriticalDone() ) {
			return;
		}

		$lock = new Lock( self::LOCK_NAME, self::LOCK_TTL_SECONDS );
		if ( ! $lock->lock() ) {
			return;
		}

		try {
			$this->runAccountsStep();
			$this->runFeedSettingsStep();
			$this->runFeedSourcesStep();
		} catch ( \Throwable $e ) {
			update_option( self::OPT_LAST_ERROR, $e->getMessage(), false );
			Logger::error(
				'legacy/migration',
				'Legacy critical migration failed.',
				[
					'error' => $e->getMessage(),
				]
			);
		} finally {
			$lock->unlock();
		}
	}

	public function isFullDone(): bool {
		return $this->isFlagEnabled( self::OPT_DONE );
	}

	public function isCriticalDone(): bool {
		return $this->isFlagEnabled( self::OPT_ACCOUNTS_DONE )
			&& $this->isFlagEnabled( self::OPT_FEED_SETTINGS_DONE )
			&& $this->isFlagEnabled( self::OPT_FEED_FILTERS_DONE )
			&& $this->isFlagEnabled( self::OPT_FEED_SOURCES_DONE );
	}

	public function shouldBootstrapForUpgrade(): bool {
		return Plugin::wasInstalledBefore( '3.0.0' ) || $this->legacyAccountIds() !== [];
	}

	private function runAccountsStep(): void {
		if ( $this->isFlagEnabled( self::OPT_ACCOUNTS_DONE ) ) {
			return;
		}

		$ids = $this->legacyAccountIds();
		if ( $ids === [] ) {
			update_option( self::OPT_ACCOUNTS_DONE, 1, false );
			return;
		}

		$hasFailures = false;

		foreach ( $ids as $legacyId ) {
			$meta = get_post_meta( $legacyId, LegacyAccountPostType::META_KEY_ACCOUNT, true );
			if ( ! is_array( $meta ) ) {
				continue;
			}

			try {
				$this->upsertLegacyAccount( $legacyId, $meta );
			} catch ( \Throwable $e ) {
				$hasFailures = true;
				Logger::error(
					'legacy/migration/accounts',
					'Legacy account migration failed.',
					[
						'legacyAccountId' => $legacyId,
						'error'           => $e->getMessage(),
					]
				);
			}
		}

		if ( $hasFailures ) {
			return;
		}

		$this->resetAccountLookup();
		update_option( self::OPT_ACCOUNTS_DONE, 1, false );
	}

	private function runFeedSourcesStep(): void {
		if ( $this->isFlagEnabled( self::OPT_FEED_SOURCES_DONE ) ) {
			return;
		}

		$feeds = $this->feeds->all();
		foreach ( $feeds as $feed ) {
			try {
				$this->syncFeedSources->handle( $feed );
			} catch ( \Throwable $e ) {
				Logger::warning(
					'legacy/migration/feed_sources',
					'Skipping feed source migration for feed.',
					[
						'feedId' => $feed->id(),
						'error'  => $e->getMessage(),
					]
				);
			}
		}

		$this->disableReconnectRequiredSources();
		update_option( self::OPT_FEED_SOURCES_DONE, 1, false );
	}

	private function runFeedSettingsStep(): void {
		if ( $this->isFlagEnabled( self::OPT_FEED_SETTINGS_DONE ) && $this->isFlagEnabled( self::OPT_FEED_FILTERS_DONE ) ) {
			return;
		}

		$hasFailures = false;

		foreach ( $this->feeds->all() as $feed ) {
			$settings   = $feed->settings()->toArray();
			$normalized = $this->normalizeLegacyFeedSettings( $settings );

			if ( $normalized === $settings ) {
				continue;
			}

			try {
				$feed->replaceSettings( FeedSettings::fromArray( $normalized ) );
				$this->feeds->save( $feed );
			} catch ( \Throwable $e ) {
				$hasFailures = true;
				Logger::warning(
					'legacy/migration/feed_settings',
					'Skipping feed settings migration for feed.',
					[
						'feedId' => $feed->id(),
						'error'  => $e->getMessage(),
					]
				);
			}
		}

		if ( $hasFailures ) {
			return;
		}

		update_option( self::OPT_FEED_SETTINGS_DONE, 1, false );
		update_option( self::OPT_FEED_FILTERS_DONE, 1, false );
	}

	private function runMediaStep(): bool {
		if ( $this->isFlagEnabled( self::OPT_MEDIA_DONE ) ) {
			return true;
		}

		$cursor = (int) get_option( self::OPT_MEDIA_CURSOR, 0 );
		$ids    = $this->legacyMediaIdsAfter( $cursor, self::LEGACY_MEDIA_BATCH );
		if ( $ids === [] ) {
			update_option( self::OPT_MEDIA_DONE, 1, false );
			return true;
		}

		$rowsBySource = [];
		foreach ( $ids as $postId ) {
			try {
				$migrated = $this->mapLegacyMediaPost( $postId );
				if ( $migrated === null ) {
					continue;
				}

				$sourceKey = trim( (string) ( $migrated['source']['sourceKey'] ?? '' ) );
				if ( $sourceKey === '' ) {
					continue;
				}

				$this->ensureSourceRow( $migrated['source'] );

				if ( ! isset( $rowsBySource[ $sourceKey ] ) ) {
					$rowsBySource[ $sourceKey ] = [];
				}

				$rowsBySource[ $sourceKey ][] = $migrated['row'];
			} catch ( \Throwable $e ) {
				Logger::warning(
					'legacy/migration/media',
					'Skipping legacy media migration for post.',
					[
						'legacyMediaPostId' => $postId,
						'error'             => $e->getMessage(),
					]
				);
			}
		}

		foreach ( $rowsBySource as $sourceKey => $rows ) {
			try {
				$this->mediaPosts->save( (string) $sourceKey, $rows );
			} catch ( \Throwable $e ) {
				Logger::warning(
					'legacy/migration/media',
					'Bulk legacy media save failed, retrying row by row.',
					[
						'sourceKey' => (string) $sourceKey,
						'error'     => $e->getMessage(),
					]
				);

				$this->saveLegacyMediaRowsIndividually( (string) $sourceKey, $rows );
			}
		}

		$newCursor = (int) end( $ids );
		update_option( self::OPT_MEDIA_CURSOR, $newCursor, false );

		if ( count( $ids ) < self::LEGACY_MEDIA_BATCH ) {
			update_option( self::OPT_MEDIA_DONE, 1, false );
			return true;
		}

		return false;
	}

	private function saveLegacyMediaRowsIndividually( string $sourceKey, array $rows ): void {
		foreach ( $rows as $row ) {
			try {
				$this->mediaPosts->save( $sourceKey, [ $row ] );
			} catch ( \Throwable $e ) {
				Logger::warning(
					'legacy/migration/media',
					'Skipping legacy media row after individual retry failure.',
					[
						'sourceKey' => $sourceKey,
						'igMediaId' => is_array( $row ) ? (string) ( $row['ig_media_id'] ?? '' ) : '',
						'error'     => $e->getMessage(),
					]
				);
			}
		}
	}

	private function runFrontIndexStep(): void {
		if ( $this->isFlagEnabled( self::OPT_INDEX_DONE ) ) {
			return;
		}

		$feeds = $this->feeds->all();
		foreach ( $feeds as $feed ) {
			try {
				$this->frontIndex->rebuildIndex( $feed->id() );
			} catch ( \Throwable $e ) {
				Logger::warning(
					'legacy/migration/front_index',
					'Front index rebuild failed for legacy feed.',
					[
						'feedId' => $feed->id(),
						'error'  => $e->getMessage(),
					]
				);
			}
		}

		update_option( self::OPT_INDEX_DONE, 1, false );
	}

	private function markDone(): void {
		delete_option( self::OPT_LAST_ERROR );
		update_option( self::OPT_DONE, 1, false );
		update_option( self::OPT_COMPLETED_AT, gmdate( 'c' ), false );
	}

	private function upsertLegacyAccount( int $legacyId, array $meta ): void {
		global $wpdb;

		$igAccountId = trim( (string) ( $meta['id'] ?? '' ) );
		if ( $igAccountId === '' ) {
			return;
		}

		$table = $this->accountsTable->table_name();
		$now   = current_time( 'mysql', true );

		$avatar = trim( (string) ( $meta['avatarOverwritten'] ?? '' ) );
		if ( $avatar === '' ) {
			$avatar = trim( (string) ( $meta['avatar'] ?? '' ) );
		}

		$biography = trim( (string) ( $meta['biographyOverwritten'] ?? '' ) );
		if ( $biography === '' ) {
			$biography = (string) ( $meta['biography'] ?? '' );
		}

		$accountType = strtolower( trim( (string) ( $meta['accountType'] ?? self::DEFAULT_ACCOUNT_TYPE ) ) );
		if ( $accountType === '' ) {
			$accountType = self::DEFAULT_ACCOUNT_TYPE;
		}

		$connectType = trim( (string) ( $meta['connectType'] ?? '' ) );
		if ( $connectType === '' ) {
			$connectType = $this->guessConnectType( $accountType );
		}

		$accessToken = trim( (string) ( $meta['accessToken'] ?? '' ) );
		$tokenPair   = $this->cipher->encrypt( $accessToken );

		$data = [
			'ig_account_id'            => $igAccountId,
			'account_type'             => $accountType,
			'connect_type'             => $connectType,
			'name'                     => (string) ( $meta['name'] ?? '' ),
			'username'                 => trim( (string) ( $meta['username'] ?? '' ) ),
			'access_token'             => $tokenPair[0],
			'access_token_iv'          => $tokenPair[1],
			'avatar'                   => $avatar,
			'biography'                => $biography,
			'media_count'              => (int) ( $meta['mediaCount'] ?? 0 ),
			'followers_count'          => (int) ( $meta['followersCount'] ?? 0 ),
			'follows_count'            => (int) ( $meta['followsCount'] ?? 0 ),
			'token_expires'            => max( 0, (int) ( $meta['tokenExpires'] ?? 0 ) ),
			'token_refresh_attempt_at' => $this->normalizeDateTimeOrNull( $meta['tokenRefreshAttemptAt'] ?? null ),
			'last_update'              => $this->normalizeLastUpdate( $meta['lastUpdate'] ?? null ),
			'updated_at'               => $now,
		];

		$existsById = $this->accountExistsById( $legacyId );
		$idByIg     = $this->accountIdByIgAccountId( $igAccountId );

		if ( $existsById ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				$data,
				[ 'id' => $legacyId ],
				[
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				],
				[ '%d' ]
			);

			return;
		}

		if ( $idByIg > 0 ) {
			$targetId = $idByIg;
			if ( $idByIg !== $legacyId && ! $this->accountExistsById( $legacyId ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$moved = $wpdb->update(
					$table,
					[ 'id' => $legacyId ],
					[ 'id' => $idByIg ],
					[ '%d' ],
					[ '%d' ]
				);

				if ( $moved !== false ) {
					$targetId = $legacyId;
				}
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				$data,
				[ 'id' => $targetId ],
				[
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				],
				[ '%d' ]
			);

			return;
		}

		$insert               = $data;
		$insert['id']         = $legacyId;
		$insert['created_at'] = $now;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$ok = $wpdb->insert(
			$table,
			$insert,
			[
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		if ( $ok === false ) {
			throw new \RuntimeException( 'Failed to migrate account id=' . $legacyId . ' error=' . (string) $wpdb->last_error );
		}
	}

	/**
	 * @return int[]
	 */
	private function legacyAccountIds(): array {
		$q = new \WP_Query(
			[
				'post_type'              => self::LEGACY_ACCOUNT_CPT,
				'posts_per_page'         => -1,
				'post_status'            => 'any',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( ! is_array( $q->posts ) || $q->posts === [] ) {
			return [];
		}

		return array_values( array_filter( array_map( 'intval', $q->posts ) ) );
	}

	/**
	 * @return int[]
	 */
	private function legacyMediaIdsAfter( int $cursor, int $limit ): array {
		global $wpdb;

		$cursor = max( 0, $cursor );
		$limit  = max( 1, $limit );

		$sql = "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = %s
			  AND ID > %d
			ORDER BY ID ASC
			LIMIT %d
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				$sql,
				self::LEGACY_MEDIA_CPT,
				$cursor,
				$limit
			)
		);

		if ( ! is_array( $ids ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'intval', $ids ) ) );
	}

	/**
	 * @return array|null
	 */
	private function mapLegacyMediaPost( int $postId ): ?array {
		$legacySource = trim( (string) get_post_meta( $postId, LegacyMediaPostType::SOURCE, true ) );
		$username     = trim( (string) get_post_meta( $postId, LegacyMediaPostType::USERNAME, true ) );

		$source = $this->mapLegacySource( $legacySource, $username );
		if ( $source === null ) {
			return null;
		}

		$igMediaId = trim( (string) get_post_meta( $postId, LegacyMediaPostType::MEDIA_ID, true ) );
		if ( $igMediaId === '' ) {
			return null;
		}

		$postedAt = $this->normalizeDateTime( get_post_meta( $postId, LegacyMediaPostType::DATE, true ) );
		if ( $postedAt === '' ) {
			return null;
		}

		$caption = get_post_meta( $postId, LegacyMediaPostType::CAPTION, true );
		$caption = is_scalar( $caption ) ? html_entity_decode( (string) $caption, ENT_QUOTES, 'UTF-8' ) : '';

		$url = trim( (string) get_post_meta( $postId, LegacyMediaPostType::URL, true ) );
		if ( $url === '' ) {
			$url = trim( (string) get_post_meta( $postId, LegacyMediaPostType::MEDIA_URL, true ) );
		}

		$row = [
			'ig_media_id'        => $igMediaId,
			'media_type'         => trim( (string) get_post_meta( $postId, LegacyMediaPostType::MEDIA_TYPE, true ) ),
			'media_product_type' => trim( (string) get_post_meta( $postId, LegacyMediaPostType::MEDIA_PRODUCT_TYPE, true ) ),
			'url'                => $url,
			'permalink'          => trim( (string) get_post_meta( $postId, LegacyMediaPostType::PERMALINK, true ) ),
			'username'           => $username,
			'video_url'          => trim( (string) get_post_meta( $postId, LegacyMediaPostType::VIDEO_URL, true ) ),
			'posted_at'          => $postedAt,
			'comments_count'     => (int) get_post_meta( $postId, LegacyMediaPostType::COMMENTS_COUNT, true ),
			'likes_count'        => (int) get_post_meta( $postId, LegacyMediaPostType::LIKES_COUNT, true ),
			'caption'            => $caption,
			'children_json'      => $this->normalizeChildrenJson( get_post_meta( $postId, LegacyMediaPostType::CHILDREN, true ) ),
		];

		return [
			'source' => $source,
			'row'    => $row,
		];
	}

	/**
	 * @return array|null
	 */
	private function mapLegacySource( string $legacySource, string $fallbackUsername ): ?array {
		$legacySource = trim( $legacySource );
		if ( $legacySource === '' ) {
			$account = $this->findAccountByIdentifier( $fallbackUsername );
			if ( $account === null ) {
				return null;
			}

			return $this->buildAccountSource( $account );
		}

		if ( strpos( $legacySource, 'acc:' ) === 0 ) {
			$igAccountId = trim( substr( $legacySource, 4 ) );
			if ( $igAccountId === '' ) {
				return null;
			}
			$account   = $this->findAccountByIdentifier( $igAccountId );
			$accountId = $account instanceof Account ? $account->id() : 0;

			return [
				'kind'      => Source::KIND_ACCOUNT,
				'sourceKey' => 'acc:' . $igAccountId,
				'fetchKey'  => $igAccountId,
				'accountId' => $accountId,
			];
		}

		if ( strpos( $legacySource, 'tagged:' ) === 0 ) {
			$igAccountId = trim( substr( $legacySource, 7 ) );
			if ( $igAccountId === '' ) {
				return null;
			}
			$account   = $this->findAccountByIdentifier( $igAccountId );
			$accountId = $account instanceof Account ? $account->id() : 0;

			return [
				'kind'      => Source::KIND_TAGGED,
				'sourceKey' => 'tagged:' . $igAccountId,
				'fetchKey'  => $igAccountId,
				'accountId' => $accountId,
			];
		}

		if ( strpos( $legacySource, 'tag:' ) === 0 ) {
			$tag = $this->normalizeTag( substr( $legacySource, 4 ) );
			if ( $tag === '' ) {
				return null;
			}

			return $this->buildHashtagSource( $tag );
		}

		$parts = explode( '|', $legacySource, 2 );
		if ( count( $parts ) < 2 ) {
			$account = $this->findAccountByIdentifier( $legacySource );
			if ( $account === null ) {
				return null;
			}

			return $this->buildAccountSource( $account );
		}

		$identifier = trim( $parts[0] );
		$type       = strtolower( trim( $parts[1] ) );

		if ( $type === self::LEGACY_SOURCE_TAGGED ) {
			$account = $this->findAccountByIdentifier( $identifier );
			if ( $account === null ) {
				return null;
			}

			return [
				'kind'      => Source::KIND_TAGGED,
				'sourceKey' => Source::tagged( $account->igAccountId() )->dbSourceKey(),
				'fetchKey'  => $account->igAccountId(),
				'accountId' => $account->id(),
			];
		}

		if ( $type === self::LEGACY_SOURCE_TOP || $type === self::LEGACY_SOURCE_RECENT ) {
			$tag = $this->normalizeTag( $identifier );
			if ( $tag === '' ) {
				return null;
			}

			return $this->buildHashtagSource( $tag );
		}

		$account = $this->findAccountByIdentifier( $identifier );
		if ( $account === null ) {
			return null;
		}

		return $this->buildAccountSource( $account );
	}

	private function buildAccountSource( Account $account ): array {
		$igAccountId = trim( $account->igAccountId() );
		if ( $igAccountId === '' ) {
			throw new \RuntimeException( 'Account has empty igAccountId for account source migration.' );
		}

		return [
			'kind'      => Source::KIND_ACCOUNT,
			'sourceKey' => Source::accountSourceKey( $igAccountId ),
			'fetchKey'  => $igAccountId,
			'accountId' => $account->id(),
		];
	}

	private function buildHashtagSource( string $tag ): array {
		$businessAccount = $this->findFirstBusinessAccountId();

		return [
			'kind'      => Source::KIND_HASHTAG,
			'sourceKey' => Source::hashtag( $tag )->dbSourceKey(),
			'fetchKey'  => $tag,
			'accountId' => $businessAccount,
		];
	}

	private function ensureSourceRow( array $source ): void {
		$sourceKey = isset( $source['sourceKey'] ) ? trim( (string) $source['sourceKey'] ) : '';
		if ( $sourceKey === '' ) {
			return;
		}

		if ( $this->sources->getByKey( $sourceKey ) !== null ) {
			return;
		}

		$kind      = isset( $source['kind'] ) ? (string) $source['kind'] : Source::KIND_ACCOUNT;
		$fetchKey  = isset( $source['fetchKey'] ) ? trim( (string) $source['fetchKey'] ) : '';
		$accountId = isset( $source['accountId'] ) ? (int) $source['accountId'] : 0;

		$sourceId = $this->sources->save(
			$kind,
			$sourceKey,
			$accountId > 0 ? $accountId : null,
			$fetchKey !== '' ? $fetchKey : $sourceKey
		);

		if ( $sourceId > 0 && $kind === Source::KIND_ACCOUNT ) {
			$this->sources->addPinnedByKey( $sourceKey );
		}
	}

	private function disableReconnectRequiredSources(): void {
		$legacyAccountIds = $this->legacyAccountIds();
		if ( $legacyAccountIds === [] ) {
			return;
		}

		foreach ( $legacyAccountIds as $legacyId ) {
			$meta = get_post_meta( $legacyId, LegacyAccountPostType::META_KEY_ACCOUNT, true );
			if ( ! is_array( $meta ) ) {
				continue;
			}

			$issues = isset( $meta['issues'] ) && is_array( $meta['issues'] ) ? $meta['issues'] : [];
			if ( empty( $issues['reconnectRequired'] ) ) {
				continue;
			}

			$rows = $this->sources->getSourcesByAccountIds( [ $legacyId ] );
			foreach ( $rows as $row ) {
				$sourceId = isset( $row['id'] ) ? (int) $row['id'] : 0;
				if ( $sourceId > 0 ) {
					$this->sources->disable( $sourceId );
				}
			}
		}
	}

	private function findAccountByIdentifier( string $value ): ?Account {
		$value = trim( ltrim( $value, '@' ) );
		if ( $value === '' ) {
			return null;
		}

		$this->warmAccountLookup();

		$keyLower = strtolower( $value );
		if ( isset( $this->accountsByUsername[ $keyLower ] ) ) {
			return $this->accountsByUsername[ $keyLower ];
		}

		if ( isset( $this->accountsByIgId[ $value ] ) ) {
			return $this->accountsByIgId[ $value ];
		}

		return null;
	}

	private function findFirstBusinessAccountId(): int {
		$this->warmAccountLookup();

		foreach ( $this->accountsById as $account ) {
			$type = strtolower( trim( $account->accountType() ) );
			if ( $type === 'business' || $type === 'media_creator' ) {
				return $account->id();
			}
		}

		return 0;
	}

	private function warmAccountLookup(): void {
		if ( is_array( $this->accountsById ) ) {
			return;
		}

		$this->accountsById       = [];
		$this->accountsByUsername = [];
		$this->accountsByIgId     = [];

		foreach ( $this->accounts->all() as $account ) {
			$this->accountsById[ $account->id() ] = $account;

			$username = strtolower( trim( ltrim( $account->username(), '@' ) ) );
			if ( $username !== '' ) {
				$this->accountsByUsername[ $username ] = $account;
			}

			$igAccountId = trim( $account->igAccountId() );
			if ( $igAccountId !== '' ) {
				$this->accountsByIgId[ $igAccountId ] = $account;
			}
		}
	}

	private function resetAccountLookup(): void {
		$this->accountsById       = null;
		$this->accountsByUsername = null;
		$this->accountsByIgId     = null;
	}

	private function normalizeDateTime( $value ): string {
		$normalized = $this->normalizeDateTimeOrNull( $value );
		return $normalized !== null ? $normalized : '';
	}

	private function normalizeDateTimeOrNull( $value ): ?string {
		if ( $value === null || $value === '' ) {
			return null;
		}

		try {
			if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
				$timestamp = (int) $value;
				if ( $timestamp <= 0 ) {
					return null;
				}

				$date = new \DateTimeImmutable( '@' . $timestamp );
				return $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			}

			if ( is_string( $value ) ) {
				$date = new \DateTimeImmutable( $value, new \DateTimeZone( 'UTC' ) );
				return $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			}
		} catch ( \Throwable $e ) {
			return null;
		}

		return null;
	}

	private function normalizeChildrenJson( $value ): string {
		if ( is_array( $value ) ) {
			$json = wp_json_encode( $value );
			return is_string( $json ) ? $json : '';
		}

		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		if ( function_exists( 'is_serialized' ) && is_serialized( $value ) ) {
			$unserialized = maybe_unserialize( $value );
			if ( is_array( $unserialized ) ) {
				$json = wp_json_encode( $unserialized );
				return is_string( $json ) ? $json : '';
			}
		}

		$decoded = json_decode( $value, true );
		if ( is_array( $decoded ) ) {
			return $value;
		}

		return '';
	}

	private function normalizeTag( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		if ( $value[0] === '#' ) {
			$value = substr( $value, 1 );
		}

		return strtolower( trim( $value ) );
	}

	private function normalizeLastUpdate( $value ): ?string {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$value = trim( (string) $value );
		return $value !== '' ? $value : null;
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	private function normalizeLegacyFeedSettings( array $settings ): array {
		return $this->legacySettingsMapper->mapForMigration( $settings );
	}

	private function guessConnectType( string $accountType ): string {
		$accountType = strtolower( trim( $accountType ) );

		if ( $accountType === 'business_basic' ) {
			return 'instagram';
		}

		return self::DEFAULT_CONNECT_TYPE;
	}

	private function isFlagEnabled( string $optionName ): bool {
		$value = get_option( $optionName, false );

		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value === 1;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			return in_array( $value, [ '1', 'true', 'yes', 'on' ], true );
		}

		return false;
	}

	private function accountExistsById( int $id ): bool {
		global $wpdb;

		if ( $id <= 0 ) {
			return false;
		}

		$table = $this->accountsTable->table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d LIMIT 1", $id ) );

		return (int) $value > 0;
	}

	private function accountIdByIgAccountId( string $igAccountId ): int {
		global $wpdb;

		$igAccountId = trim( $igAccountId );
		if ( $igAccountId === '' ) {
			return 0;
		}

		$table = $this->accountsTable->table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ig_account_id = %s LIMIT 1", $igAccountId ) );

		return (int) $value;
	}
}
