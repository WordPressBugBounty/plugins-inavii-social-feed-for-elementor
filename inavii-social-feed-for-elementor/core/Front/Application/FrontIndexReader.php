<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Application;

use Inavii\Instagram\FrontIndex\Application\FrontIndexService;

final class FrontIndexReader {
	private FrontIndexService $frontIndex;

	public function __construct( FrontIndexService $frontIndex ) {
		$this->frontIndex = $frontIndex;
	}

	public function load( int $feedId ): array {
		try {
			$index = $this->frontIndex->getIndex( $feedId );
		} catch ( \Throwable $e ) {
			return [];
		}

		return is_array( $index ) ? $index : [];
	}

	public function extractMediaIds( array $index, array $media ): array {
		$mediaIds = isset( $index['mediaIds'] ) && is_array( $index['mediaIds'] ) ? $index['mediaIds'] : [];
		if ( $mediaIds === [] ) {
			$mediaIds = array_column( $media, 'id' );
		}

		$ids = [];
		foreach ( $mediaIds as $id ) {
			$id = (int) $id;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
