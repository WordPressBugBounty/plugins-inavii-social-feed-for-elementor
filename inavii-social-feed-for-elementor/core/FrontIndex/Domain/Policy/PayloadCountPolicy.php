<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Domain\Policy;

final class PayloadCountPolicy {
	public const DEFAULT_BASE_PAYLOAD_LIMIT = 40;

	private const MAX_PAYLOAD_LIMIT     = 200;
	private const HIGHLIGHT_LAYOUT_MODE = 'highlight';
	private const HIGHLIGHT_LOAD_STEP   = 9;

	public static function resolveBaseLimit(): int {
		$limit = (int) apply_filters(
			'inavii/social-feed/front-index/payload_limit',
			self::DEFAULT_BASE_PAYLOAD_LIMIT
		);

		return self::normalizePayloadLimit( $limit );
	}

	public function resolve( array $feed, int $baseLimit ): int {
		$baseLimit = max( 1, $baseLimit );
		$step      = $this->resolveStep( $feed );
		if ( $step <= 1 ) {
			return $baseLimit;
		}

		return $this->alignToStep( $baseLimit, $step );
	}

	private function resolveStep( array $feed ): int {
		if ( $this->resolveLayoutMode( $feed ) === self::HIGHLIGHT_LAYOUT_MODE ) {
			return self::HIGHLIGHT_LOAD_STEP;
		}

		$settings   = isset( $feed['settings'] ) && is_array( $feed['settings'] ) ? $feed['settings'] : [];
		$design     = isset( $settings['design'] ) && is_array( $settings['design'] ) ? $settings['design'] : [];
		$feedLayout = isset( $design['feedLayout'] ) && is_array( $design['feedLayout'] ) ? $design['feedLayout'] : [];
		$raw        = $feedLayout['numberOfPosts'] ?? null;

		$single = $this->toPositiveInt( $raw );
		if ( $single !== null ) {
			return $single;
		}

		if ( is_array( $raw ) ) {
			$steps = [];

			foreach ( [ 'desktop', 'tablet', 'mobile' ] as $size ) {
				$candidate = $this->toPositiveInt( $raw[ $size ] ?? null );
				if ( $candidate !== null ) {
					$steps[] = $candidate;
				}
			}

			foreach ( $raw as $size => $candidate ) {
				if ( $size === 'desktop' || $size === 'tablet' || $size === 'mobile' ) {
					continue;
				}

				$normalized = $this->toPositiveInt( $candidate );
				if ( $normalized !== null ) {
					$steps[] = $normalized;
				}
			}

			if ( $steps !== [] ) {
				return max( $steps );
			}
		}

		return 1;
	}

	private function resolveLayoutMode( array $feed ): string {
		$settings   = isset( $feed['settings'] ) && is_array( $feed['settings'] ) ? $feed['settings'] : [];
		$design     = isset( $settings['design'] ) && is_array( $settings['design'] ) ? $settings['design'] : [];
		$feedLayout = isset( $design['feedLayout'] ) && is_array( $design['feedLayout'] ) ? $design['feedLayout'] : [];

		$candidates = [
			$feedLayout['viewVariant'] ?? null,
			$feedLayout['view'] ?? null,
		];

		foreach ( $candidates as $candidate ) {
			if ( ! is_scalar( $candidate ) ) {
				continue;
			}

			$value = strtolower( trim( (string) $candidate ) );
			if ( $value === '' ) {
				continue;
			}

			return $value;
		}

		return '';
	}

	private function alignToStep( int $value, int $step ): int {
		$step      = max( 1, $step );
		$remainder = $value % $step;
		if ( $remainder === 0 ) {
			return $value;
		}

		return $value + ( $step - $remainder );
	}

	private function toPositiveInt( $value ): ?int {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$value = (int) $value;
		if ( $value <= 0 ) {
			return null;
		}

		return $value;
	}

	private static function normalizePayloadLimit( int $limit ): int {
		if ( $limit < 1 ) {
			return self::DEFAULT_BASE_PAYLOAD_LIMIT;
		}

		if ( $limit > self::MAX_PAYLOAD_LIMIT ) {
			return self::MAX_PAYLOAD_LIMIT;
		}

		return $limit;
	}
}
