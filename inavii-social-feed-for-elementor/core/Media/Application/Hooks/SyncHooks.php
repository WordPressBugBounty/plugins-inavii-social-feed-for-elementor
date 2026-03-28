<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application\Hooks;

use Inavii\Instagram\Logger\Logger;

final class SyncHooks {
	public function register(): void {
		add_action( 'inavii/social-feed/media/sync/error', [ $this, 'onSyncError' ], 10, 2 );
	}

	public function onSyncError( string $sourceLabel, string $message ): void {
		Logger::error(
			'media/sync',
			'Media sync error',
			[
				'source'  => $sourceLabel,
				'message' => $message,
			]
		);
	}
}
