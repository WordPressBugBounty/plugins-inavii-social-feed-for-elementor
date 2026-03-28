<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Application;

use Inavii\Instagram\Media\Application\UseCase\FetchMedia;
use Inavii\Instagram\Media\Domain\SyncResult;
use Inavii\Instagram\Media\Source\Domain\Source;

final class MediaFetchService {
	private FetchMedia $fetchMedia;

	public function __construct(
		FetchMedia $fetchMedia
	) {
		$this->fetchMedia = $fetchMedia;
	}

	public function fetch( Source $source ): SyncResult {
		return $this->fetchMedia->handle( $source );
	}
}
