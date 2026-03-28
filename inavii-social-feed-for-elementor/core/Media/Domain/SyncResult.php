<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Domain;

final class SyncResult {

	/** @var int */
	public int $sourcesTotal = 0;

	/** @var int */
	public int $sourcesOk = 0;

	/** @var int */
	public int $itemsFetched = 0;

	/** @var int */
	public int $itemsSaved = 0;

	/** @var SyncError[] */
	private array $errors = [];

	public function addError( SyncError $error ): void {
		$this->errors[] = $error;
	}

	/**
	 * @return SyncError[]
	 */
	public function errors(): array {
		return $this->errors;
	}

	public function hasErrors(): bool {
		return $this->errors !== [];
	}
}
