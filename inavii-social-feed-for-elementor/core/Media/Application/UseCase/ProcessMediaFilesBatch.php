<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application\UseCase;

use Inavii\Instagram\Media\Files\MediaQueue;
use Inavii\Instagram\Media\Files\MediaFileWorker;

final class ProcessMediaFilesBatch {

	private MediaQueue $queue;
	private MediaFileWorker $worker;

	public function __construct( MediaQueue $queue, MediaFileWorker $worker ) {
		$this->queue  = $queue;
		$this->worker = $worker;
	}

	/**
	 * Process one batch of queued downloads.
	 *
	 * @return int Number of successfully cached items
	 */
	public function handle( int $batchSize = 20 ): int {
		try {
			$this->queue->updateStaleDownloading( 30, 200 );
		} catch ( \Throwable $e ) {
			// non-fatal
			unset( $e );
		}

		$rows = $this->queue->findBatch( $batchSize );

		if ( $rows === [] ) {
			return 0;
		}

		$savedCount = 0;

		foreach ( $rows as $row ) {
			if ( $this->worker->processFile( $row ) ) {
				$savedCount++;
			}
		}

		return $savedCount;
	}
}
