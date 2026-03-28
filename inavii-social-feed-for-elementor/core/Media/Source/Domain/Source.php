<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Source\Domain;

/**
 * Value object representing a media source.
 *
 * NOTE:
 * - For ACCOUNT source, the final DB source_key is computed by the fetcher
 *   (we want acc:<ig_account_id>, and ig id is known after credentials lookup).
 * - For TAGGED/HASHTAG sources, DB source_key is deterministic.
 */
final class Source {

	public const KIND_ACCOUNT = 'accounts';
	public const KIND_TAGGED  = 'tagged';
	public const KIND_HASHTAG = 'hashtag';

	/** @var string */
	private $kind;

	/** @var int */
	private $accountId;

	/** @var string */
	private $value;

	/** @var int */
	private $fetchAccountId;

	private function __construct( string $kind, int $accountId, string $value, int $fetchAccountId = 0 ) {
		$this->kind           = $kind;
		$this->accountId      = $accountId;
		$this->value          = $value;
		$this->fetchAccountId = $fetchAccountId;
	}

	public static function account( int $accountId ): self {
		if ( $accountId <= 0 ) {
			throw new \InvalidArgumentException( 'Account id must be > 0' );
		}

		return new self( self::KIND_ACCOUNT, $accountId, '' );
	}

	public static function accountSourceKey( string $igAccountId ): string {
		$igAccountId = trim( $igAccountId );

		if ( $igAccountId === '' ) {
			throw new \InvalidArgumentException( 'igAccountId cannot be empty' );
		}

		return 'acc:' . $igAccountId;
	}

	public static function tagged( string $username, int $fetchAccountId = 0 ): self {
		$u = self::normalizeHandle( $username );
		if ( $u === '' ) {
			throw new \InvalidArgumentException( 'Tagged username cannot be empty' );
		}

		return new self( self::KIND_TAGGED, 0, $u, $fetchAccountId );
	}

	public static function hashtag( string $tag, int $fetchAccountId = 0 ): self {
		$t = self::normalizeTag( $tag );
		if ( $t === '' ) {
			throw new \InvalidArgumentException( 'Hashtag cannot be empty' );
		}

		return new self( self::KIND_HASHTAG, 0, $t, $fetchAccountId );
	}

	public function kind(): string {
		return $this->kind;
	}

	public function isAccount(): bool {
		return $this->kind === self::KIND_ACCOUNT;
	}

	public function accountId(): int {
		if ( $this->kind !== self::KIND_ACCOUNT ) {
			throw new \LogicException( 'accountId() is only available for ACCOUNT source' );
		}

		return $this->accountId;
	}

	/**
	 * Normalized value:
	 * - TAGGED: username without @, lower-case
	 * - HASHTAG: tag without #, lower-case
	 */
	public function value(): string {
		return $this->value;
	}

	public function fetchAccountId(): int {
		return $this->fetchAccountId;
	}

	/**
	 * Deterministic DB source_key for non-account sources.
	 * For account sources, the fetcher must compute: acc:<ig_account_id>
	 */
	public function dbSourceKey(): string {
		if ( $this->kind === self::KIND_ACCOUNT ) {
			throw new \LogicException( 'dbSourceKey() for ACCOUNT is computed by fetcher (acc:<ig_account_id>)' );
		}

		if ( $this->kind === self::KIND_TAGGED ) {
			return 'tagged:' . $this->value;
		}

		if ( $this->kind === self::KIND_HASHTAG ) {
			return 'tag:' . $this->value;
		}

		throw new \LogicException( 'Unknown source kind: ' . $this->kind );
	}

	private static function normalizeHandle( string $v ): string {
		$v = trim( $v );
		if ( $v !== '' && $v[0] === '@' ) {
			$v = substr( $v, 1 );
		}

		// Instagram handles are effectively ASCII, strtolower is enough.
		return strtolower( $v );
	}

	private static function normalizeTag( string $v ): string {
		$v = trim( $v );
		if ( $v !== '' && $v[0] === '#' ) {
			$v = substr( $v, 1 );
		}

		return strtolower( $v );
	}
}
