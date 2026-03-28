<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application;

use Inavii\Instagram\Media\Storage\MediaRepository;
use Inavii\Instagram\Media\Source\Domain\Source;

final class MediaSourceService {
	private MediaRepository $media;

	public function __construct( MediaRepository $media ) {
		$this->media = $media;
	}

	public function registerAccountSource( int $accountId, string $igAccountId ): void {
		if ( $accountId <= 0 ) {
			throw new \InvalidArgumentException( 'Account id must be > 0' );
		}

		$igAccountId = trim( $igAccountId );
		if ( $igAccountId === '' ) {
			throw new \InvalidArgumentException( 'igAccountId cannot be empty' );
		}

		$sourceKey = Source::accountSourceKey( $igAccountId );

		$this->media->sources()->save(
			Source::KIND_ACCOUNT,
			$sourceKey,
			$accountId,
			$igAccountId
		);

		$this->media->sources()->addPinnedByKey( $sourceKey );
		$this->media->sources()->clearFailureByKey( $sourceKey );
	}

	public function isDisabledByKey( string $sourceKey ): bool {
		return $this->media->sources()->isDisabledByKey( $sourceKey );
	}

	public function markAuthFailureByKey( string $sourceKey, string $error ): int {
		$row = $this->media->sources()->getByKey( $sourceKey );
		if ( ! is_array( $row ) ) {
			return 0;
		}

		$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
		if ( $id <= 0 ) {
			return 0;
		}

		$this->media->sources()->markAuthFailure( $id, $error );

		return $id;
	}
}
