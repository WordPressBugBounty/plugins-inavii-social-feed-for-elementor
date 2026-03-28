<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Fetcher;

/**
 * Result of fetching remote media for a single source.
 */
final class FetchResponse {

	/** @var string Final DB source_key, e.g. acc:<ig_account_id> */
	private string $sourceKey;

	/** @var array */
	private array $items;

	/**
	 * @param string $sourceKey
	 * @param array $items
	 */
	public function __construct( string $sourceKey, array $items ) {
		$this->sourceKey = $sourceKey;
		$this->items     = $items;
	}

	public function sourceKey(): string {
		return $this->sourceKey;
	}

	/**
	 * @return array
	 */
	public function items(): array {
		return $this->items;
	}
}
