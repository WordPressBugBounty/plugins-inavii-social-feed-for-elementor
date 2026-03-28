<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Source\Application;

use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Source\Domain\SourceSyncPolicy;
use Inavii\Instagram\Media\Storage\MediaRepository;
use Inavii\Instagram\Media\Application\MediaFetchService;
use Inavii\Instagram\Logger\Logger;

final class SyncSources {

	private MediaRepository $repository;
	private MediaFetchService $fetchService;
	private SourceSyncPolicy $policy;

	public function __construct(
		MediaRepository $repository,
		MediaFetchService $fetchService,
		SourceSyncPolicy $policy
	) {
		$this->repository   = $repository;
		$this->fetchService = $fetchService;
		$this->policy       = $policy;
	}

	public function handle( int $limit = 50 ): void {
		$sources = $this->repository->sources()->getToSync( $limit );
		$this->handleRows( $sources );
	}

	/**
	 * @param array $rows
	 */
	public function handleRows( array $rows ): void {
		foreach ( $rows as $row ) {
			$source = $this->rowToSource( $row );
			if ( $source === null ) {
				continue;
			}

			$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
			try {
				$result  = $this->fetchService->fetch( $source );
				$failure = $this->policy->resolveFailure( $result );

				if ( $failure !== null ) {
					if ( ! empty( $failure['auth'] ) ) {
						$this->repository->sources()->markAuthFailure( $id, $failure['message'] );
						$this->logFailure( 'auth', $row, $failure['message'], null );
						continue;
					}

					$attempts    = isset( $row['sync_attempts'] ) ? (int) $row['sync_attempts'] : 0;
					$nextMinutes = $this->policy->resolveBackoffMinutes( $attempts + 1 );
					$this->repository->sources()->markSyncFailure( $id, $failure['message'], $nextMinutes );
					$this->logFailure( 'sync', $row, $failure['message'], $nextMinutes );
					continue;
				}

				$this->repository->sources()->markSyncSuccess( $id );
			} catch ( \Throwable $e ) {
				if ( $this->policy->isAuthFailure( $e ) ) {
					$this->repository->sources()->markAuthFailure( $id, $e->getMessage() );
					$this->logFailure( 'auth', $row, $e->getMessage(), null );
					continue;
				}

				$attempts    = isset( $row['sync_attempts'] ) ? (int) $row['sync_attempts'] : 0;
				$nextMinutes = $this->policy->resolveBackoffMinutes( $attempts + 1 );
				$this->repository->sources()->markSyncFailure( $id, $e->getMessage(), $nextMinutes );
				$this->logFailure( 'sync', $row, $e->getMessage(), $nextMinutes );
			}
		}
	}

	private function rowToSource( array $row ): ?Source {
		$kind = isset( $row['kind'] ) ? (string) $row['kind'] : '';
		if ( $kind === Source::KIND_ACCOUNT ) {
			$accountId = isset( $row['account_id'] ) ? (int) $row['account_id'] : 0;
			if ( $accountId <= 0 ) {
				return null;
			}
			return Source::account( $accountId );
		}

		if ( $kind === Source::KIND_TAGGED ) {
			$key       = isset( $row['fetch_key'] ) ? (string) $row['fetch_key'] : '';
			$accountId = isset( $row['account_id'] ) ? (int) $row['account_id'] : 0;
			return $key !== '' ? Source::tagged( $key, $accountId ) : null;
		}

		if ( $kind === Source::KIND_HASHTAG ) {
			$key       = isset( $row['fetch_key'] ) ? (string) $row['fetch_key'] : '';
			$accountId = isset( $row['account_id'] ) ? (int) $row['account_id'] : 0;
			return $key !== '' ? Source::hashtag( $key, $accountId ) : null;
		}

		return null;
	}

	private function logFailure( string $type, array $row, string $message, ?int $nextMinutes ): void {
		$context = [
			'source_id'  => (int) ( $row['id'] ?? 0 ),
			'source_key' => (string) ( $row['source_key'] ?? '' ),
			'kind'       => (string) ( $row['kind'] ?? '' ),
			'attempts'   => (int) ( $row['sync_attempts'] ?? 0 ),
		];

		if ( $nextMinutes !== null ) {
			$context['backoff_minutes'] = $nextMinutes;
		}

		$label = $type === 'auth' ? 'Auth failure' : 'Sync failure';
		if ( $type === 'auth' ) {
			Logger::error( 'media/source_sync', $label . ': ' . $message, $context );
			return;
		}

		Logger::warning( 'media/source_sync', $label . ': ' . $message, $context );
	}
}
