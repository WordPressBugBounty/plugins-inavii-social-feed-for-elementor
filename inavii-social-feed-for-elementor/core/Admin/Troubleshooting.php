<?php

namespace Inavii\Instagram\Admin;

use Inavii\Instagram\Cron\CronLogger;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Services\Instagram\Post\BusinessPosts;
use Inavii\Instagram\Services\Instagram\Post\PrivatePosts;
use Inavii\Instagram\Wp\AppGlobalSettings;

class Troubleshooting {

	public static function checkCronStatus() {
		$wpCronEnabled = ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON );

		$updateScheduled   = wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
		$refreshScheduled  = wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_REFRESH_TOKEN_TASK );

		$updateHasCallback  = has_action( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
		$refreshHasCallback = has_action( AppGlobalSettings::CRON_SCHEDULE_REFRESH_TOKEN_TASK );

		$bothScheduled = $updateScheduled && $refreshScheduled;
		$bothCallbacks = $updateHasCallback && $refreshHasCallback;

		return [
			'wpCronEnabled' => [
				'status'  => $wpCronEnabled,
				'message' => $wpCronEnabled ? 'WP-Cron is enabled.' : 'WP-Cron is disabled.',
			],
			'cronScheduled' => [
				'status'  => (bool) $bothScheduled,
				'message' => $bothScheduled
					? 'Both cron tasks are scheduled correctly.'
					: 'One or both cron tasks are not scheduled.',
			],
			'hookHasCallbacks' => [
				'status'  => (bool) $bothCallbacks,
				'message' => $bothCallbacks
					? 'Both cron hooks have attached callbacks.'
					: 'One or both cron hooks do not have attached callbacks.',
			],
		];
	}


	public static function cronLogger() {
		return CronLogger::getStatus();
	}

	public static function fixCronIssues() {
		$actions = [];

		$actions = array_merge(
			$actions,
			self::fixUpdateMediaCron(),
			self::fixRefreshTokenCron()
		);

		return [
			'status'  => empty($actions) ? 'nothing-to-fix' : 'fixed-or-advised',
			'actions' => $actions
		];
	}

	private static function fixUpdateMediaCron(): array {
		$actions = [];

		if ( ! wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK ) ) {
			wp_schedule_event( time(), 'hourly', AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
			$actions[] = 'Scheduled missing cron task AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK).';
		}

		if ( ! has_action( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK ) ) {
			$actions[] = 'No callback attached toAppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK.';
		}

		return $actions;
	}

	private static function fixRefreshTokenCron(): array {
		$actions = [];

		if ( ! wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_REFRESH_TOKEN_TASK ) ) {
			wp_schedule_event( time(), 'weekly', AppGlobalSettings::CRON_SCHEDULE_REFRESH_TOKEN_TASK );
			$actions[] = 'Scheduled missing cron task AppGlobalSettings::CRON_SCHEDULE_REFRESH_TOKEN_TASK).';
		}

		if ( ! has_action( AppGlobalSettings::CRON_SCHEDULE_REFRESH_TOKEN_TASK ) ) {
			$actions[] = 'No callback attached toAppGlobalSettings::CRON_SCHEDULE_REFRESH_TOKEN_TASK.';
		}

		return $actions;
	}

	public static function runCronNow() {
		do_action( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
	}
}