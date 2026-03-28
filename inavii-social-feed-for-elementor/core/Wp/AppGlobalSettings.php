<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Wp;

class AppGlobalSettings {

	const CRON_SCHEDULE_UPDATE_MEDIA_TASK           = 'inavii/social-feed/media/sync';
	private static bool $fetchLimitFilterRegistered = false;

	public function __construct() {
		if ( ! self::$fetchLimitFilterRegistered ) {
			add_filter( 'inavii/social-feed/media/fetch_limit', [ $this, 'filterFetchLimit' ], 5, 1 );
			self::$fetchLimitFilterRegistered = true;
		}
	}

	public function saveNumberOfPostsImported( int $numberOfPostsImported = 100 ): void {
		update_option( 'inavii_social_feed_number_of_posts_imported', $numberOfPostsImported );
	}

	public function getNumberOfPostsImportedRaw(): int {
		$default = (int) get_option( 'inavii_social_feed_number_of_posts_imported', 100 );
		return $this->normalizeLimit( $default, 100 );
	}

	public function getNumberOfPostsImported(): int {
		$default = $this->getNumberOfPostsImportedRaw();
		$limit   = apply_filters( 'inavii/social-feed/media/fetch_limit', $default );

		return $this->normalizeLimit( $limit, $default );
	}

	public function saveCronInterval( string $cronInterval = 'hourly' ): void {
		update_option( 'inavii_social_feed_cron_interval', $cronInterval );
	}

	public function getCronInterval(): string {
		return (string) get_option( 'inavii_social_feed_cron_interval', 'hourly' );
	}

	public function saveEmailNotifications( bool $emailNotifications = false ): void {
		update_option( 'inavii_social_feed_email_notifications', $emailNotifications );
	}

	public function getEmailNotifications(): bool {
		return (bool) get_option( 'inavii_social_feed_email_notifications', false );
	}

	public function getEmail(): string {
		$email = get_option( 'inavii_social_feed_email_to_notifications', false );

		if ( ! $email ) {
			return get_option( 'admin_email', '' );
		}

		return $email;
	}

	public function getRenderOption(): string {
		return (string) get_option( 'inavii_social_feed_render_type', 'PHP' );
	}

	public function saveRenderOption( string $renderOption ): void {
		update_option( 'inavii_social_feed_render_type', $renderOption );
	}

	public function saveEmail( string $email = '' ): void {
		update_option( 'inavii_social_feed_email_to_notifications', $email );
	}

	private function normalizeLimit( $limit, int $fallback ): int {
		$limit = is_numeric( $limit ) ? (int) $limit : $fallback;
		return max( 1, $limit );
	}

	public function filterFetchLimit( $limit ): int {
		return $this->getNumberOfPostsImportedRaw();
	}

	public function getAvailableSchedules(): array {
		$schedules = wp_get_schedules();
		return array_filter(
			$schedules,
			function ( $schedule ) {
				return $schedule['interval'] >= 900 && $schedule['interval'] <= 604800;
			}
		);
	}
}
