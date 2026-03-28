<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain;

class Account {
	/** @var int */
	private $id;
	/** @var string */
	private $igAccountId;
	/** @var string */
	private $accountType;
	/** @var string */
	private $connectType;
	/** @var string */
	private $name;
	/** @var string */
	private $username;
	/** @var string */
	private $accessToken;
	/** @var string */
	private $avatar;
	/** @var string */
	private $biography;
	/** @var int */
	private $mediaCount;
	/** @var int */
	private $followersCount;
	/** @var int */
	private $followsCount;
	/** @var int */
	private $tokenExpires;
	/** @var int */
	private $tokenRefreshAttemptAt;
	/** @var string|null ISO8601 */
	private $lastUpdate;

	private function __construct(
		int $id,
		string $igAccountId,
		string $accountType,
		string $connectType,
		string $name,
		string $username,
		string $accessToken,
		string $avatar,
		string $biography,
		int $mediaCount,
		int $followersCount,
		int $followsCount,
		int $tokenExpires,
		int $tokenRefreshAttemptAt,
		?string $lastUpdate
	) {
		$this->id                    = $id;
		$this->igAccountId           = $igAccountId;
		$this->accountType           = $accountType;
		$this->connectType           = $connectType;
		$this->name                  = $name;
		$this->username              = $username;
		$this->accessToken           = $accessToken;
		$this->avatar                = $avatar;
		$this->biography             = $biography;
		$this->mediaCount            = $mediaCount;
		$this->followersCount        = $followersCount;
		$this->followsCount          = $followsCount;
		$this->tokenExpires          = $tokenExpires;
		$this->tokenRefreshAttemptAt = $tokenRefreshAttemptAt;
		$this->lastUpdate            = $lastUpdate;
	}


	public function updateAvatar( string $avatar ): void {
		$this->avatar = $avatar;
	}

	public function updateProfile( string $name, string $username, string $biography ): void {
		$this->name      = $name;
		$this->username  = $username;
		$this->biography = $biography;
	}

	public function updateAuth( string $accountType, string $accessToken, int $tokenExpires, string $connectType = '' ): void {
		$this->accountType = $accountType;
		if ( $connectType !== '' ) {
			$this->connectType = $connectType;
		}
		$this->accessToken  = $accessToken;
		$this->tokenExpires = $tokenExpires;
	}

	public function markTokenRefreshAttemptAt( int $timestamp ): void {
		$this->tokenRefreshAttemptAt = $timestamp;
	}

	public function updateStats( int $mediaCount, int $followersCount, int $followsCount ): void {
		$this->mediaCount     = $mediaCount;
		$this->followersCount = $followersCount;
		$this->followsCount   = $followsCount;
	}

	public function touchLastUpdate( string $lastUpdate ): void {
		$this->lastUpdate = trim( $lastUpdate );
	}

	public function applySnapshot( AccountSnapshot $snapshot ): void {
		$this->updateProfile(
			$snapshot->name(),
			$snapshot->userName(),
			$snapshot->biography() ?? ''
		);
		$this->updateAuth(
			$snapshot->accountType(),
			$snapshot->accessToken(),
			$snapshot->tokenExpires(),
			$snapshot->connectType()
		);
		$this->updateStats(
			$snapshot->mediaCount(),
			$snapshot->followersCount(),
			$snapshot->followsCount()
		);
		$this->updateAvatar( $snapshot->avatar() );
	}

	public static function fromArray( array $meta ): self {
		return new self(
			(int) ( $meta['id'] ?? 0 ),
			(string) ( $meta['igAccountId'] ?? '' ),
			(string) ( $meta['accountType'] ?? '' ),
			(string) ( $meta['connectType'] ?? '' ),
			(string) ( $meta['name'] ?? '' ),
			(string) ( $meta['username'] ?? '' ),
			(string) ( $meta['accessToken'] ?? '' ),
			(string) ( $meta['avatar'] ?? '' ),
			(string) ( $meta['biography'] ?? '' ),
			(int) ( $meta['mediaCount'] ?? 0 ),
			(int) ( $meta['followersCount'] ?? 0 ),
			(int) ( $meta['followsCount'] ?? 0 ),
			(int) ( $meta['tokenExpires'] ?? 0 ),
			(int) ( $meta['tokenRefreshAttemptAt'] ?? 0 ),
			isset( $meta['lastUpdate'] ) ? (string) $meta['lastUpdate'] : null
		);
	}

	public static function fromSnapshot( AccountSnapshot $user ): self {
		return new self(
			0,
			$user->id(),
			$user->accountType(),
			$user->connectType(),
			$user->name(),
			$user->userName(),
			$user->accessToken(),
			$user->avatar(),
			$user->biography() ?? '',
			$user->mediaCount(),
			$user->followersCount(),
			$user->followsCount(),
			$user->tokenExpires(),
			0,
			null
		);
	}

	public function id(): int {
		return $this->id;
	}

	public function igAccountId(): string {
		return $this->igAccountId;
	}

	public function hasIgAccountId(): bool {
		return trim( $this->igAccountId ) !== '';
	}

	public function accountType(): string {
		return $this->accountType;
	}

	public function connectType(): string {
		return $this->connectType;
	}

	public function name(): string {
		return $this->name;
	}

	public function username(): string {
		return $this->username;
	}

	public function accessToken(): string {
		return $this->accessToken;
	}

	public function hasAccessToken(): bool {
		return trim( $this->accessToken ) !== '';
	}

	public function avatar(): string {
		return $this->avatar;
	}

	public function biography(): string {
		return $this->biography;
	}

	public function mediaCount(): int {
		return $this->mediaCount;
	}

	public function followersCount(): int {
		return $this->followersCount;
	}

	public function followsCount(): int {
		return $this->followsCount;
	}

	public function tokenExpires(): int {
		return $this->tokenExpires;
	}

	public function tokenRefreshAttemptAt(): int {
		return $this->tokenRefreshAttemptAt;
	}

	public function lastUpdate(): ?string {
		return $this->lastUpdate;
	}

	public function instagramProfileLink(): string {
		return 'https://www.instagram.com/' . $this->username;
	}
}
