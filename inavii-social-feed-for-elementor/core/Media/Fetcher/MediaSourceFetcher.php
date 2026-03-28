<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Fetcher;

use Inavii\Instagram\Media\Source\Domain\Source;

/**
 * Resolves and executes the proper fetcher for a media source.
 */
final class MediaSourceFetcher {
	/** @var array<string,SourceFetcher> */
	private array $fetchers;

	/**
	 * @param SourceFetcher[] $fetchers
	 */
	public function __construct( array $fetchers ) {
		$this->fetchers = [];

		foreach ( $fetchers as $fetcher ) {
			if ( ! $fetcher instanceof SourceFetcher ) {
				continue;
			}

			$this->fetchers[ $fetcher->kind() ] = $fetcher;
		}
	}

	public function fetch( Source $source ): FetchResponse {
		$kind = $source->kind();

		if ( ! isset( $this->fetchers[ $kind ] ) ) {
			throw new \RuntimeException( 'No fetcher registered for source kind: ' . $kind );
		}

		$fetcher = $this->fetchers[ $kind ];

		return $fetcher->fetch( $source );
	}
}
