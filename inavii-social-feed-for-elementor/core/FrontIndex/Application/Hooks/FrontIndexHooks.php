<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application\Hooks;

use Inavii\Instagram\FrontIndex\Application\FrontIndexService;

final class FrontIndexHooks {
	private FrontIndexService $index;

	public function __construct( FrontIndexService $index ) {
		$this->index = $index;
	}

	public function register(): void {
		add_action( 'inavii/social-feed/front-index/rebuild', [ $this, 'onRebuild' ], 10, 1 );
		add_action( 'inavii/social-feed/front-index/delete', [ $this, 'onDelete' ], 10, 1 );
	}

	/**
	 * @param array|mixed $payload
	 */
	public function onRebuild( $payload ): void {
		if ( ! is_array( $payload ) ) {
			return;
		}

		$feedId = isset( $payload['feedId'] ) ? (int) $payload['feedId'] : 0;
		if ( $feedId > 0 ) {
			$this->index->rebuildIndex( $feedId );
			return;
		}

		$sourceKey = isset( $payload['sourceKey'] ) ? trim( (string) $payload['sourceKey'] ) : '';
		if ( $sourceKey !== '' ) {
			$this->index->rebuildBySource( $sourceKey );
			return;
		}

		$igAccountId = isset( $payload['igAccountId'] ) ? trim( (string) $payload['igAccountId'] ) : '';
		if ( $igAccountId !== '' ) {
			$this->index->rebuildByAccount( $igAccountId );
		}
	}

	/**
	 * @param array|mixed $payload
	 */
	public function onDelete( $payload ): void {
		if ( ! is_array( $payload ) ) {
			return;
		}

		$feedId = isset( $payload['feedId'] ) ? (int) $payload['feedId'] : 0;
		if ( $feedId <= 0 ) {
			return;
		}

		$this->index->deleteIndex( $feedId );
	}
}
