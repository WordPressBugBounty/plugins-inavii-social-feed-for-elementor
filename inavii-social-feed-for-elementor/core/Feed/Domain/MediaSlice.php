<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Domain;

final class MediaSlice {
	/** @var array */
	private array $posts;
	private int $total;

	/**
	 * @param array $posts
	 * @param int   $total
	 */
	public function __construct( array $posts, int $total ) {
		$this->posts = $posts;
		$this->total = $total;
	}

	public static function empty(): self {
		return new self( [], 0 );
	}

	/**
	 * @return array
	 */
	public function posts(): array {
		return $this->posts;
	}

	public function total(): int {
		return $this->total;
	}

	// Backward-compatible accessors used by front endpoints.
	public function getPosts(): array {
		return $this->posts();
	}

	public function getTotal(): int {
		return $this->total();
	}
}
