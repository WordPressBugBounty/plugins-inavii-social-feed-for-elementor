<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain;

final class AccountSnapshot {
	private string $id;
	private string $accountType;
	private string $connectType;
	private string $name;
	private string $userName;
	private string $accessToken;
	private string $avatar;
	private int $mediaCount;
	private int $followersCount;
	private int $followsCount;
	private int $tokenExpires;
	private ?string $biography;

	public function __construct(
		string $id,
		string $accountType,
		string $connectType,
		string $name,
		string $userName,
		string $accessToken,
		string $avatar,
		int $mediaCount,
		int $followersCount,
		int $followsCount,
		int $tokenExpires,
		?string $biography
	) {
		$this->id             = $id;
		$this->accountType    = $accountType;
		$this->connectType    = $connectType;
		$this->name           = $name;
		$this->userName       = $userName;
		$this->accessToken    = $accessToken;
		$this->avatar         = $avatar;
		$this->mediaCount     = $mediaCount;
		$this->followersCount = $followersCount;
		$this->followsCount   = $followsCount;
		$this->tokenExpires   = $tokenExpires;
		$this->biography      = $biography;
	}

	public static function fromApiPayload(
		array $payload,
		string $accessToken,
		int $tokenExpires,
		string $connectType,
		string $defaultType,
		int $now
	): self {
		$accountType = self::normalizeAccountType( $payload['account_type'] ?? $defaultType );
		$token       = self::normalizeString( $accessToken );
		$connectType = ConnectType::resolve( $token, '', $accountType, $connectType );

		return new self(
			self::normalizeString( $payload['id'] ?? '' ),
			$accountType,
			$connectType,
			self::normalizeString( $payload['name'] ?? '' ),
			self::normalizeString( $payload['username'] ?? '' ),
			$token,
			self::normalizeString( $payload['profile_picture_url'] ?? '' ),
			(int) ( $payload['media_count'] ?? 0 ),
			(int) ( $payload['followers_count'] ?? 0 ),
			(int) ( $payload['follows_count'] ?? 0 ),
			TokenExpiry::normalize( $tokenExpires, $now ),
			self::normalizeBiography( $payload )
		);
	}

	public function id(): string {
		return $this->id;
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

	public function userName(): string {
		return $this->userName;
	}

	public function accessToken(): string {
		return $this->accessToken;
	}

	public function avatar(): string {
		return $this->avatar;
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

	public function biography(): ?string {
		return $this->biography;
	}

	private static function normalizeString( $value ): string {
		if ( is_scalar( $value ) ) {
			return trim( (string) $value );
		}

		return '';
	}

	private static function normalizeAccountType( $value ): string {
		$normalized = strtolower( self::normalizeString( $value ) );
		return $normalized !== '' ? $normalized : 'personal';
	}

	private static function normalizeBiography( array $payload ): ?string {
		if ( ! array_key_exists( 'biography', $payload ) ) {
			return null;
		}

		$bio = self::normalizeString( $payload['biography'] );
		return $bio !== '' ? $bio : '';
	}
}
