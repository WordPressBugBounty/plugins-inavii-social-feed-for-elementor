<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Admin;

use Inavii\Instagram\Config\Env;
use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\Freemius\FreemiusAccess;

final class ReviewNoticePolicy {
	private const DELAY_SECONDS          = 7 * DAY_IN_SECONDS;
	private const DISPLAY_WINDOW_SECONDS = 60 * DAY_IN_SECONDS;
	private const MIN_ELIGIBLE_VERSION   = '3.0.1';

	public function shouldShow(): bool {
		try {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return false;
			}

			if ( $this->isDismissed() ) {
				return false;
			}

			if ( FreemiusAccess::canUsePremiumCode() ) {
				return false;
			}

			if ( Plugin::wasInstalledBefore( self::MIN_ELIGIBLE_VERSION ) ) {
				return false;
			}

			if ( ! $this->hasRequiredUsageSignals() ) {
				return false;
			}

			return $this->isDueByTime( Plugin::activatedDate() );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	public function displayName(): string {
		$currentUser = wp_get_current_user();
		$displayName = ( $currentUser instanceof \WP_User && is_string( $currentUser->display_name ) )
			? trim( $currentUser->display_name )
			: '';

		if ( $displayName === '' ) {
			return __( 'there', 'inavii-social-feed' );
		}

		return $displayName;
	}

	public function logoUrl(): string {
		$assetsUrl = rtrim( (string) Env::$assets_url, '/' );
		if ( $assetsUrl !== '' ) {
			return $assetsUrl . '/images/inavii-logo-fs.png';
		}

		if ( defined( 'INAVII_INSTAGRAM_URL' ) ) {
			return rtrim( (string) INAVII_INSTAGRAM_URL, '/' ) . '/assets/images/inavii-logo-fs.png';
		}

		return '';
	}

	public function isDismissed(): bool {
		return (bool) get_option( ReviewNotice::DISMISS_OPTION, false );
	}

	private function hasRequiredUsageSignals(): bool {
		return $this->hasAnyFeed();
	}

	private function hasAnyFeed(): bool {
		if ( ! class_exists( '\WP_Query' ) ) {
			return false;
		}

		$query = new \WP_Query(
			[
				'post_type'              => FeedRepository::CPT,
				'posts_per_page'         => 1,
				'post_status'            => 'any',
				'no_found_rows'          => true,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		return ! empty( $query->posts );
	}


	private function isDueByTime( string $activatedAt ): bool {
		$activatedTimestamp = $this->parseActivationTimestamp( $activatedAt );
		if ( $activatedTimestamp <= 0 ) {
			return false;
		}

		$now       = time();
		$startsAt  = $activatedTimestamp + self::DELAY_SECONDS;
		$expiresAt = $startsAt + self::DISPLAY_WINDOW_SECONDS;

		return $now >= $startsAt && $now < $expiresAt;
	}

	private function parseActivationTimestamp( string $activatedAt ): int {
		$activatedAt = trim( $activatedAt );
		if ( $activatedAt === '' ) {
			return 0;
		}

		if ( function_exists( 'wp_timezone' ) ) {
			$date = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $activatedAt, wp_timezone() );
			if ( $date instanceof \DateTimeImmutable ) {
				return $date->getTimestamp();
			}
		}

		$fallbackTimestamp = strtotime( $activatedAt );

		return $fallbackTimestamp === false ? 0 : (int) $fallbackTimestamp;
	}
}
