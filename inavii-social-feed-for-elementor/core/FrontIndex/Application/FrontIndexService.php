<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application;

use Inavii\Instagram\FrontIndex\Domain\Policy\PayloadCountPolicy;
use Inavii\Instagram\FrontIndex\Storage\FrontIndexRepository;
use Inavii\Instagram\Media\Storage\MediaRepository;
use Inavii\Instagram\Media\Source\Domain\Source;

final class FrontIndexService {
	private FrontIndexRepository $index;
	private BuildFrontIndex $builder;
	private MediaRepository $media;

	public function __construct(
		FrontIndexRepository $index,
		BuildFrontIndex $builder,
		MediaRepository $media
	) {
		$this->index   = $index;
		$this->builder = $builder;
		$this->media   = $media;
	}

	public function getIndex( int $feedId ): array {
		$cached = $this->index->getByFeedId( $feedId );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		return $this->rebuildIndex( $feedId );
	}

	public function rebuildIndex( int $feedId ): array {
		$baseLimit    = $this->resolvePayloadLimit();
		$payloadLimit = $this->builder->recommendPayloadLimit( $feedId, $baseLimit );
		$payload      = $this->builder->handle( $feedId, $payloadLimit );
		$mediaIds     = isset( $payload['mediaIds'] ) && is_array( $payload['mediaIds'] ) ? $payload['mediaIds'] : [];
		$this->index->save( $feedId, $payload['meta'], $payload['media'], $mediaIds );

		return $payload;
	}

	public function deleteIndex( int $feedId ): void {
		$this->index->deleteByFeedId( $feedId );
	}

	public function clearIndex(): void {
		$this->index->clearAll();
	}

	public function rebuildBySource( string $sourceKey ): void {
		$sourceKey = trim( $sourceKey );
		if ( $sourceKey === '' ) {
			return;
		}

		$row = $this->media->sources()->getByKey( $sourceKey );
		if ( ! is_array( $row ) ) {
			return;
		}

		$sourceId = isset( $row['id'] ) ? (int) $row['id'] : 0;
		if ( $sourceId <= 0 ) {
			return;
		}

		$feedIds = $this->media->feedSources()->getFeedIdsBySourceId( $sourceId );
		foreach ( $feedIds as $feedId ) {
			try {
				$this->rebuildIndex( (int) $feedId );
			} catch ( \Throwable $e ) {
				continue;
			}
		}
	}

	public function rebuildByAccount( string $igAccountId ): void {
		$igAccountId = trim( $igAccountId );
		if ( $igAccountId === '' ) {
			return;
		}

		$this->rebuildBySource( Source::accountSourceKey( $igAccountId ) );
	}

	private function resolvePayloadLimit(): int {
		return PayloadCountPolicy::resolveBaseLimit();
	}
}
