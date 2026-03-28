<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Fetcher;

use Inavii\Instagram\Media\Source\Domain\Source;

final class UnsupportedSourceFetcher implements SourceFetcher {
	private string $kind;
	private string $message;

	public function __construct( string $kind, string $message ) {
		$this->kind    = trim( $kind );
		$this->message = trim( $message );
	}

	public function kind(): string {
		return $this->kind;
	}

	public function fetch( Source $source ): FetchResponse {
		if ( $source->kind() !== $this->kind ) {
			throw new \InvalidArgumentException( 'UnsupportedSourceFetcher expects source kind: ' . $this->kind );
		}

		throw new \RuntimeException( $this->message !== '' ? $this->message : 'This source kind requires the Pro version.' );
	}
}
