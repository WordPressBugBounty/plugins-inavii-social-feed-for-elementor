<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application\Hooks;

use Inavii\Instagram\Media\Application\MediaCleanupService;
use Inavii\Instagram\Media\Application\MediaFileService;
use Inavii\Instagram\Media\Application\MediaSourceService;
use Inavii\Instagram\Media\Source\Domain\Source;

final class AccountHooks {
	private MediaSourceService $sources;
	private MediaCleanupService $cleanup;
	private MediaFileService $files;

	public function __construct(
		MediaSourceService $sources,
		MediaCleanupService $cleanup,
		MediaFileService $files
	) {
		$this->sources = $sources;
		$this->cleanup = $cleanup;
		$this->files   = $files;
	}

	public function register(): void {
		add_action( 'inavii/social-feed/account/connected', [ $this, 'onAccountConnected' ], 10, 3 );
		add_action( 'inavii/social-feed/account/deleted', [ $this, 'onAccountDeleted' ], 10, 3 );
	}

	public function onAccountConnected( int $accountId, string $igAccountId, string $connectType ): void {
		$this->sources->registerAccountSource( $accountId, $igAccountId );
	}

	public function onAccountDeleted( int $accountId, string $igAccountId, string $avatarUrl ): void {
		if ( $avatarUrl !== '' ) {
			$this->files->deleteFromUrl( $avatarUrl );
		}

		$igAccountId = trim( $igAccountId );
		if ( $igAccountId === '' ) {
			return;
		}

		$sourceKey = Source::accountSourceKey( $igAccountId );
		$this->cleanup->detachSourceFromFeeds( $sourceKey );
		$this->cleanup->unpinSource( $sourceKey );
		$this->cleanup->cleanupSourceIfUnused( $sourceKey );
	}
}
