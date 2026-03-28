<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain;

final class ConnectType {
	public const INSTAGRAM = 'instagram';
	public const FACEBOOK  = 'facebook';

	public static function forAccount( Account $account ): string {
		return self::resolve(
			$account->accessToken(),
			'',
			$account->accountType(),
			$account->connectType()
		);
	}

	public static function isInstagramAccount( Account $account ): bool {
		return self::forAccount( $account ) === self::INSTAGRAM;
	}

	public static function resolve(
		string $accessToken,
		string $businessId = '',
		string $accountType = '',
		string $storedConnectType = ''
	): string {
		$stored = self::normalize( $storedConnectType );
		if ( $stored !== '' ) {
			return $stored;
		}

		$token = trim( $accessToken );
		if ( strpos( $token, 'IG' ) === 0 ) {
			return self::INSTAGRAM;
		}

		if ( strpos( $token, 'EA' ) === 0 ) {
			return self::FACEBOOK;
		}

		if ( trim( $businessId ) !== '' ) {
			return self::FACEBOOK;
		}

		$type = strtolower( trim( $accountType ) );
		if ( $type === 'business' || $type === 'business_basic' || $type === 'creator' ) {
			return self::FACEBOOK;
		}

		return self::INSTAGRAM;
	}

	private static function normalize( string $value ): string {
		$value = strtolower( trim( $value ) );

		if ( $value === self::INSTAGRAM || $value === self::FACEBOOK ) {
			return $value;
		}

		return '';
	}
}
