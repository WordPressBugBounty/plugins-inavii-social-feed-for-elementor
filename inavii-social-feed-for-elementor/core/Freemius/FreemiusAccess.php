<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Freemius;

class FreemiusAccess {
	public static function version(): \Freemius {
		return inavii_social_feed_e_fs();
	}

	public static function isPremiumBuild(): bool {
		return self::withFreemius(
			static fn( \Freemius $freemius ): bool => (bool) $freemius->is__premium_only(),
			false
		);
	}

	public static function canUsePremiumCode(): bool {
		return self::withFreemius(
			static fn( \Freemius $freemius ): bool => (bool) $freemius->is__premium_only() && (bool) $freemius->can_use_premium_code(),
			false
		);
	}

	public static function canUsePlanOrTrial( string $plan ): bool {
		return self::withFreemius(
			static fn( \Freemius $freemius ): bool => self::canUsePremiumCode() && (bool) $freemius->is_plan_or_trial__premium_only( $plan ),
			false
		);
	}

	/**
	 * @template T
	 *
	 * @param callable(\Freemius):T $callback
	 * @param T                     $fallback
	 *
	 * @return T
	 */
	private static function withFreemius( callable $callback, $fallback ) {
		if ( ! function_exists( 'inavii_social_feed_e_fs' ) ) {
			return $fallback;
		}

		try {
			return $callback( self::version() );
		} catch ( \Throwable $e ) {
			return $fallback;
		}
	}
}
