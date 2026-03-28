<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Fetcher;

use Inavii\Instagram\Media\Source\Domain\Source;

interface SourceFetcher {
	public function kind(): string;

	public function fetch( Source $source ): FetchResponse;
}
