<?php
declare(strict_types=1);

namespace Inavii\Instagram\Cron;

/**
 * Small helper for WP-Cron scheduling.
 *
 * Responsibilities:
 * - register custom intervals (e.g. 5 minutes)
 * - schedule jobs if missing
 * - unschedule jobs (useful on uninstall / debug)
 */
final class Scheduler {

	public const INTERVAL_5_MIN   = 'inavii_5min';
	public const INTERVAL_1_HOUR  = 'inavii_hourly';
	public const INTERVAL_2_DAILY = 'inavii_twice_daily';
	public const INTERVAL_1_WEEK  = 'inavii_weekly';

	/**
	 * Must be called early (e.g. plugins_loaded) to register intervals.
	 */
	public function registerIntervals(): void {
		add_filter( 'cron_schedules', [ $this, 'addIntervals' ] );
	}

	/**
	 * @param array $schedules
	 * @return array
	 */
	public function addIntervals( array $schedules ): array {
		if ( ! isset( $schedules[ self::INTERVAL_5_MIN ] ) ) {
			$schedules[ self::INTERVAL_5_MIN ] = [
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => 'Every 5 Minutes (Inavii)',
			];
		}

		if ( ! isset( $schedules[ self::INTERVAL_1_HOUR ] ) ) {
			$schedules[ self::INTERVAL_1_HOUR ] = [
				'interval' => HOUR_IN_SECONDS,
				'display'  => 'Hourly (Inavii)',
			];
		}

		if ( ! isset( $schedules[ self::INTERVAL_2_DAILY ] ) ) {
			$schedules[ self::INTERVAL_2_DAILY ] = [
				'interval' => 12 * HOUR_IN_SECONDS,
				'display'  => 'Twice Daily (Inavii)',
			];
		}

		if ( ! isset( $schedules[ self::INTERVAL_1_WEEK ] ) ) {
			$schedules[ self::INTERVAL_1_WEEK ] = [
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => 'Weekly (Inavii)',
			];
		}

		return $schedules;
	}

	/**
	 * Schedule recurring event and reschedule if interval changed.
	 *
	 * @param string           $hook Hook name
	 * @param string           $interval One of WP intervals or custom (e.g. self::INTERVAL_1_HOUR)
	 * @param array $args Hook args (must match when checking scheduled)
	 */
	public function scheduleRecurring( string $hook, string $interval, array $args = [] ): void {
		$hook = $this->normalizeHook( $hook );

		if ( $hook === '' ) {
			return;
		}

		$schedules = wp_get_schedules();

		if ( ! isset( $schedules[ $interval ] ) ) {
			if ( $interval === 'hourly' && isset( $schedules[ self::INTERVAL_1_HOUR ] ) ) {
				$interval = self::INTERVAL_1_HOUR;
			} elseif ( $interval === 'weekly' && isset( $schedules[ self::INTERVAL_1_WEEK ] ) ) {
				$interval = self::INTERVAL_1_WEEK;
			}
		}

		if ( ! isset( $schedules[ $interval ] ) ) {
			$interval = isset( $schedules['twicedaily'] ) ? 'twicedaily' : self::INTERVAL_2_DAILY;
		}

		$event = wp_get_scheduled_event( $hook, $args );
		if ( $event && $event->schedule === $interval ) {
			return;
		}

		if ( $event ) {
			wp_unschedule_event( $event->timestamp, $hook, $args );
		}

		wp_schedule_event( time(), $interval, $hook, $args );
	}

	/**
	 * Schedule recurring event if it's not scheduled yet.
	 *
	 * @param string           $hook Hook name
	 * @param string           $interval One of WP intervals or custom (e.g. self::INTERVAL_5_MIN)
	 * @param array $args Hook args (must match when checking scheduled)
	 */
	public function scheduleIfMissing( string $hook, string $interval, array $args = [] ): void {
		$hook = $this->normalizeHook( $hook );
		if ( $hook === '' ) {
			return;
		}

		$next = wp_next_scheduled( $hook, $args );
		if ( $next !== false ) {
			return;
		}

		// Align to "now", WP will run it soon, then keep interval cadence
		wp_schedule_event( time(), $interval, $hook, $args );
	}

	/**
	 * Unschedule all occurrences of a hook (any args).
	 * Useful for uninstall or debugging.
	 */
	public function unscheduleAll( string $hook ): void {
		$hook = $this->normalizeHook( $hook );
		if ( $hook === '' ) {
			return;
		}

		$crons = _get_cron_array();
		if ( ! is_array( $crons ) || $crons === [] ) {
			return;
		}

		foreach ( $crons as $timestamp => $events ) {
			if ( ! is_array( $events ) || ! isset( $events[ $hook ] ) ) {
				continue;
			}

			foreach ( $events[ $hook ] as $signature => $event ) {
				$args = isset( $event['args'] ) && is_array( $event['args'] ) ? $event['args'] : [];
				wp_unschedule_event( (int) $timestamp, $hook, $args );
			}
		}
	}

	private function normalizeHook( string $hook ): string {
		return trim( $hook );
	}
}
