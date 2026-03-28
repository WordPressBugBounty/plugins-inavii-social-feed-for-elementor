<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Domain;

final class SyncError {

	/** @var int */
	private int $sourceId;

	/** @var string */
	private string $sourceLabel;

	/** @var string */
	private string $message;
	private bool $authFailure;

	public function __construct( int $sourceId, string $sourceLabel, string $message, bool $authFailure = false ) {
		$this->sourceId    = $sourceId;
		$this->sourceLabel = $sourceLabel;
		$this->message     = $message;
		$this->authFailure = $authFailure;
	}

	public function sourceId(): int {
		return $this->sourceId;
	}

	public function sourceLabel(): string {
		return $this->sourceLabel;
	}

	public function message(): string {
		return $this->message;
	}

	public function isAuthFailure(): bool {
		return $this->authFailure;
	}

	/**
	 * Convenience for logs/debug.
	 */
	public function toArray(): array {
		return [
			'sourceId'    => $this->sourceId,
			'sourceLabel' => $this->sourceLabel,
			'message'     => $this->message,
			'authFailure' => $this->authFailure,
		];
	}
}
