<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Domain\Exceptions;

final class FeedNotFoundException extends \RuntimeException {

	public function __construct( int $feedId ) {
		parent::__construct( 'Feed not found: ' . $feedId );
	}
}
