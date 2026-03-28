<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Domain;

use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;

final class FeedSources {
	/** @var int[] */
	private array $accounts;
	/** @var string[] */
	private array $hashtags;
	/** @var int[] */
	private array $tagged;
	private ?int $primaryAccountId;

	/**
	 * @param int[]    $accounts Selected account IDs.
	 * @param string[] $hashtags Selected hashtag IDs.
	 * @param int[]    $tagged Selected tagged account IDs.
	 * @param int|null $primaryAccountId Preferred primary account ID.
	 */
	public function __construct( array $accounts, array $hashtags, array $tagged, ?int $primaryAccountId = null ) {
		$this->accounts         = $this->normalizeIds( $accounts );
		$this->hashtags         = $this->normalizeHashtags( $hashtags );
		$this->tagged           = $this->normalizeIds( $tagged );
		$this->primaryAccountId = $this->normalizePrimaryAccountId( $primaryAccountId, $this->accounts );
	}

	public static function fromArray( array $data, ?ProFeaturesPolicy $proFeatures = null ): self {
		$proFeatures = $proFeatures ?? new ProFeaturesPolicy();
		$accounts    = $data['accounts'] ?? [];

		$primaryAccountId = null;
		if ( array_key_exists( 'primaryAccountId', $data ) ) {
			$primaryAccountId = is_scalar( $data['primaryAccountId'] ) ? (int) $data['primaryAccountId'] : null;
		}

		if ( ! $proFeatures->canUseMultipleSourceAccounts() && is_array( $accounts ) ) {
			$accounts = array_slice( $accounts, 0, 1 );
		}

		return new self(
			$accounts,
			$proFeatures->canUseHashtagSources() ? ( $data['hashtags'] ?? [] ) : [],
			$proFeatures->canUseTaggedPosts() ? ( $data['tagged'] ?? [] ) : [],
			$primaryAccountId
		);
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return [
			'accounts'         => $this->accounts,
			'hashtags'         => $this->hashtags,
			'tagged'           => $this->tagged,
			'primaryAccountId' => $this->primaryAccountId,
		];
	}

	/**
	 * @return int[]
	 */
	public function accounts(): array {
		return $this->accounts;
	}

	/**
	 * @return string[]
	 */
	public function hashtags(): array {
		return $this->hashtags;
	}

	/**
	 * @return int[]
	 */
	public function tagged(): array {
		return $this->tagged;
	}

	public function primaryAccountId(): ?int {
		return $this->primaryAccountId;
	}

	public function isEmpty(): bool {
		return $this->accounts === [] && $this->hashtags === [] && $this->tagged === [];
	}

	/**
	 * @return int[]
	 */
	public function allAccountIds(): array {
		return array_values( array_unique( array_merge( $this->accounts, $this->tagged ) ) );
	}

	/**
	 * @param mixed[] $values Raw source account IDs.
	 *
	 * @return int[]
	 */
	private function normalizeIds( array $values ): array {
		$ids = array_map( 'intval', $values );
		$ids = array_filter(
			$ids,
			static function ( int $id ): bool {
				return $id > 0;
			}
		);

		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param mixed[] $hashtags Raw source hashtag values.
	 *
	 * @return string[]
	 */
	private function normalizeHashtags( array $hashtags ): array {
		$out = [];
		foreach ( $hashtags as $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['id'] ) ) {
					$value = $value['id'];
				} elseif ( isset( $value['name'] ) ) {
					$value = $value['name'];
				} elseif ( isset( $value['tag'] ) ) {
					$value = $value['tag'];
				} else {
					continue;
				}
			}

			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$tag = trim( (string) $value );
			if ( $tag === '' ) {
				continue;
			}
			if ( $tag[0] === '#' ) {
				$tag = substr( $tag, 1 );
			}
			$tag = strtolower( $tag );
			if ( $tag !== '' ) {
				$out[] = $tag;
			}
		}

		$out = array_values( array_unique( $out ) );

		return $out;
	}

	/**
	 * @param int|null $primaryAccountId Candidate primary account ID.
	 * @param int[]    $accounts Selected account IDs.
	 */
	private function normalizePrimaryAccountId( ?int $primaryAccountId, array $accounts ): ?int {
		if ( $primaryAccountId === null || $primaryAccountId <= 0 ) {
			return null;
		}

		return in_array( $primaryAccountId, $accounts, true )
			? $primaryAccountId
			: null;
	}
}
