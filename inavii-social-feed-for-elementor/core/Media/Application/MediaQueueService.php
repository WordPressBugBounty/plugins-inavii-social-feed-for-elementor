<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Application;

use Inavii\Instagram\Media\Application\UseCase\EnqueueMediaFiles;
use Inavii\Instagram\Media\Application\UseCase\ProcessMediaFilesBatch;
use Inavii\Instagram\Media\Files\MediaQueue;

final class MediaQueueService {

	private EnqueueMediaFiles $queuePostFiles;
	private ProcessMediaFilesBatch $processBatch;
	private MediaQueue $queue;

	public function __construct(
		EnqueueMediaFiles $queuePostFiles,
		ProcessMediaFilesBatch $processBatch,
		MediaQueue $queue
	) {
		$this->queuePostFiles = $queuePostFiles;
		$this->processBatch   = $processBatch;
		$this->queue          = $queue;
	}

	/**
	 * Enqueue latest items for a source and return number enqueued.
	 */
	public function enqueue( string $sourceKey, int $rowsCount ): int {
		return $this->queuePostFiles->handle( $sourceKey, $rowsCount );
	}

	/**
	 * Process one batch of queued downloads.
	 *
	 * @return int Number of successfully cached items
	 */
	public function runBatch( int $batchSize = 20 ): int {
		return $this->processBatch->handle( $batchSize );
	}

	public function hasQueued(): bool {
		return $this->queue->countQueued() > 0;
	}
}
