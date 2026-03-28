<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Source\Domain;

use Inavii\Instagram\InstagramApi\InstagramApiException;
use Inavii\Instagram\Media\Domain\SyncResult;

final class SourceSyncPolicy {
	/**
	 * @var int[]
	 */
	private array $backoffScheduleMinutes;

	/**
	 * @param int[]|null $backoffScheduleMinutes An array of backoff intervals in minutes. The first element is the backoff for the first failure, the second for the second failure, and so on. If null or empty, a default schedule of [15, 30, 60, 360, 720] will be used.
	 */
	public function __construct( ?array $backoffScheduleMinutes = null ) {
		$this->backoffScheduleMinutes = $this->normalizeSchedule( $backoffScheduleMinutes );
	}

	public function resolveFailureMessage( SyncResult $result ): ?string {
		foreach ( $result->errors() as $error ) {
			$message = $error->message();
			if ( stripos( $message, 'File queue error:' ) === 0 ) {
				continue;
			}

			return $message;
		}

		return null;
	}

	/**
	 *
	 * @return array|null An array with keys 'message' (string) and 'auth' (bool) if a failure is found, or null if no relevant failure is found.
	 */
	public function resolveFailure( SyncResult $result ): ?array {
		foreach ( $result->errors() as $error ) {
			$message = $error->message();
			if ( stripos( $message, 'File queue error:' ) === 0 ) {
				continue;
			}

			$auth = $error->isAuthFailure() || $this->isAuthFailureMessage( $message );

			return [
				'message' => $message,
				'auth'    => $auth,
			];
		}

		return null;
	}

	public function isAuthFailureMessage( string $message ): bool {
		$message = strtolower( $message );

		return strpos( $message, 'access token' ) !== false
				|| strpos( $message, 'token expired' ) !== false
				|| strpos( $message, 'session has expired' ) !== false
				|| strpos( $message, 'invalid or expired' ) !== false
				|| strpos( $message, 'invalid oauth' ) !== false
				|| strpos( $message, 'permission' ) !== false;
	}

	public function isAuthFailure( \Throwable $error ): bool {
		if ( $error instanceof InstagramApiException ) {
			if ( $error->requiresReconnect() ) {
				return true;
			}
		}

		return $this->isAuthFailureMessage( $error->getMessage() );
	}

	public function resolveBackoffMinutes( int $attempts ): int {
		$index = $attempts - 1;

		if ( $index < 0 ) {
			$index = 0;
		}

		if ( $index >= count( $this->backoffScheduleMinutes ) ) {
			return $this->backoffScheduleMinutes[ count( $this->backoffScheduleMinutes ) - 1 ];
		}

		return $this->backoffScheduleMinutes[ $index ];
	}

	/**
	 * @param int[]|null $schedule
	 *
	 * @return int[]
	 */
	private function normalizeSchedule( ?array $schedule ): array {
		$defaults = [ 15, 30, 60, 360, 720 ];
		if ( ! is_array( $schedule ) || $schedule === [] ) {
			return $defaults;
		}

		$normalized = [];
		foreach ( $schedule as $minutes ) {
			$minutes = (int) $minutes;
			if ( $minutes > 0 ) {
				$normalized[] = $minutes;
			}
		}

		if ( $normalized === [] ) {
			return $defaults;
		}

		return array_values( $normalized );
	}
}
