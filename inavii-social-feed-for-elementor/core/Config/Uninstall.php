<?php
declare(strict_types=1);

namespace Inavii\Instagram\Config;

use Inavii\Instagram\Account\Cron\AccountStatsCron;
use Inavii\Instagram\Account\Cron\AccountTokenCron;
use Inavii\Instagram\Database\Tables\AccountsTable;
use Inavii\Instagram\Database\Tables\FeedSourcesTable;
use Inavii\Instagram\Database\Tables\FeedFrontCacheTable;
use Inavii\Instagram\Database\Tables\LogsTable;
use Inavii\Instagram\Database\Tables\MediaTable;
use Inavii\Instagram\Database\Tables\MediaChildrenTable;
use Inavii\Instagram\Database\Tables\SourcesTable;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyMigrationQueue;
use Inavii\Instagram\Media\Cron\MediaQueueCron;
use Inavii\Instagram\Media\Cron\MediaSourceCleanupCron;
use Inavii\Instagram\Media\Cron\MediaSyncCron;

final class Uninstall {
	private const PRESERVED_OPTIONS = [
		'inavii_social_feed_version_history',
	];

	private const EXTRA_OPTIONS = [
		'inavii_media_sync_last_run',
		'inavii_media_sync_last_success',
		'inavii_account_stats_last_run',
		'inavii_account_stats_last_success',
		'inavii_account_token_refresh_last_run',
		'inavii_account_token_refresh_last_success',
	];

	private const PREFIXED_TRANSIENTS = [
		'inavii_social_feed_',
		'inavii_lock_',
	];

	private const DIRECT_TRANSIENTS = [
		'inavii_media_sync_check',
		'inavii_cron_ping_request_lock',
	];

	public static function run(): void {
		if ( ! defined( 'ABSPATH' ) ) {
			return;
		}

		self::deleteCustomPosts();
		self::deleteUploads();
		self::unscheduleCrons();
		self::deleteOptions();
		self::dropTables();
	}

	private static function deleteCustomPosts(): void {
		$types = [ 'inavii_ig_media', 'inavii_account', 'inavii_feed' ];
		foreach ( $types as $postType ) {
			$posts = get_posts(
				[
					'post_type'   => $postType,
					'numberposts' => -1,
					'post_status' => 'any',
				]
			);

			foreach ( $posts as $post ) {
				wp_delete_post( $post->ID, true );
			}
		}
	}

	private static function deleteUploads(): void {
		$uploadDir = wp_get_upload_dir();
		$baseDir   = isset( $uploadDir['basedir'] ) ? (string) $uploadDir['basedir'] : '';
		if ( $baseDir === '' ) {
			return;
		}

		$mediaDir = Env::$media_dir !== ''
			? Env::$media_dir
			: rtrim( $baseDir, '/\\' ) . '/inavii-social-feed';

		if ( ! self::isAllowedMediaDirectory( $mediaDir, $baseDir ) || is_link( $mediaDir ) ) {
			return;
		}

		if ( ! is_dir( $mediaDir ) ) {
			return;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $mediaDir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				@rmdir( $item->getPathname() );
			} else {
				@unlink( $item->getPathname() );
			}
		}

		@rmdir( $mediaDir );
	}

	private static function unscheduleCrons(): void {
		wp_clear_scheduled_hook( 'inavii_social_feed_update_media' );
		wp_clear_scheduled_hook( 'inavii_social_feed_refresh_token' );
		wp_clear_scheduled_hook( MediaQueueCron::HOOK );
		wp_clear_scheduled_hook( MediaSyncCron::HOOK );
		wp_clear_scheduled_hook( MediaSourceCleanupCron::HOOK );
		wp_clear_scheduled_hook( AccountStatsCron::HOOK );
		wp_clear_scheduled_hook( AccountTokenCron::HOOK );
		wp_clear_scheduled_hook( LegacyMigrationQueue::HOOK );
	}

	private static function deleteOptions(): void {
		global $wpdb;

		$like  = $wpdb->esc_like( 'inavii_social_feed_' ) . '%';
		$query = $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$like
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$names = $wpdb->get_col( $query );
		if ( is_array( $names ) ) {
			foreach ( $names as $name ) {
				$name = (string) $name;
				if ( $name === '' || in_array( $name, self::PRESERVED_OPTIONS, true ) ) {
					continue;
				}

				delete_option( $name );
			}
		}

		foreach ( self::EXTRA_OPTIONS as $optionName ) {
			delete_option( $optionName );
		}

		foreach ( self::PREFIXED_TRANSIENTS as $prefix ) {
			self::deleteTransientsByPrefix( $prefix );
		}

		foreach ( self::DIRECT_TRANSIENTS as $transientKey ) {
			delete_transient( $transientKey );
		}
	}

	private static function dropTables(): void {
		global $wpdb;

		$tables = [
			$wpdb->prefix . AccountsTable::BASE_NAME,
			$wpdb->prefix . SourcesTable::BASE_NAME,
			$wpdb->prefix . FeedSourcesTable::BASE_NAME,
			$wpdb->prefix . FeedFrontCacheTable::BASE_NAME,
			$wpdb->prefix . MediaTable::BASE_NAME,
			$wpdb->prefix . MediaChildrenTable::BASE_NAME,
			$wpdb->prefix . LogsTable::BASE_NAME,
		];

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		}
	}

	private static function deleteTransientsByPrefix( string $prefix ): void {
		global $wpdb;

		$transientLike = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$query         = $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$transientLike
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$optionNames = $wpdb->get_col( $query );
		if ( ! is_array( $optionNames ) ) {
			return;
		}

		foreach ( $optionNames as $optionName ) {
			$optionName   = (string) $optionName;
			$transientKey = str_replace( '_transient_', '', $optionName );

			if ( $transientKey === '' ) {
				continue;
			}

			delete_transient( $transientKey );
		}
	}

	private static function isAllowedMediaDirectory( string $mediaDir, string $uploadsBaseDir ): bool {
		$mediaDir       = self::normalizePath( $mediaDir );
		$uploadsBaseDir = self::normalizePath( $uploadsBaseDir );

		if ( $mediaDir === '' || $uploadsBaseDir === '' ) {
			return false;
		}

		return basename( $mediaDir ) === 'inavii-social-feed'
			&& dirname( $mediaDir ) === $uploadsBaseDir;
	}

	private static function normalizePath( string $path ): string {
		$path = trim( $path );
		if ( $path === '' ) {
			return '';
		}

		$resolved = realpath( $path );
		if ( is_string( $resolved ) && $resolved !== '' ) {
			$path = $resolved;
		}

		return rtrim( wp_normalize_path( $path ), '/' );
	}
}
