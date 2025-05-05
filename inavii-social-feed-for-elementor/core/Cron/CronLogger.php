<?php

namespace Inavii\Instagram\Cron;

class CronLogger {
	private static $optionKey = 'inavii_social_feed_cron_last_status';

	private static function ensureOptionExists(): void {
		if (get_option(self::$optionKey) === false) {
			add_option(self::$optionKey, [], '', false);
		}
	}

	public static function logStart(): void {
		self::ensureOptionExists();

		update_option(self::$optionKey, [
			'status'     => 'running',
			'started_at' => current_time('mysql'),
			'ended_at'   => null,
			'error'      => null,
		]);
	}

	public static function logSuccess(): void {
		self::ensureOptionExists();

		$current = get_option(self::$optionKey, []);
		$current['status']   = 'success';
		$current['ended_at'] = current_time('mysql');

		update_option(self::$optionKey, $current);
	}

	public static function logError(string $errorMessage): void {
		self::ensureOptionExists();

		$current = get_option(self::$optionKey, []);
		$current['status']   = 'error';
		$current['ended_at'] = current_time('mysql');
		$current['error']    = $errorMessage;

		update_option(self::$optionKey, $current);
	}

	public static function getStatus(): array {
		return get_option(self::$optionKey, [
			'status'     => 'unknown',
			'started_at' => null,
			'ended_at'   => null,
			'error'      => null,
		]);
	}

	public static function clear(): void {
		delete_option(self::$optionKey);
	}
}
