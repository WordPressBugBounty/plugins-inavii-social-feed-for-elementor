<?php
declare(strict_types=1);

namespace Inavii\Instagram\Front;

final class PayloadRegistry {

	/** @var array */
	private array $payloads = [];

	/**
	 * @param string $key A unique key to identify the payload, e.g. feed ID or something like that.
	 * @param array  $payload The payload data to be stored, which will be later printed in the footer as JSON. It should be an array that can be safely encoded to JSON.
	 *
	 * @return void
	 */
	public function add( string $key, array $payload ): void {
		$this->payloads[ $key ] = $payload;
	}

	/** @return array */
	public function all(): array {
		return $this->payloads;
	}

	public function isEmpty(): bool {
		return $this->payloads === [];
	}
}
