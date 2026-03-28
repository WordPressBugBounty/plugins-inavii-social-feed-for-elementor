<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config\Troubleshooting;

use Inavii\Instagram\Account\Cron\AccountStatsCron;
use Inavii\Instagram\Account\Cron\AccountTokenCron;
use Inavii\Instagram\Media\Cron\MediaQueueCron;
use Inavii\Instagram\Media\Cron\MediaSourceCleanupCron;
use Inavii\Instagram\Media\Cron\MediaSyncCron;

final class CronDiagnostics {
	/**
	 * @return array
	 */
	public function status(): array {
		return [
			$this->row( MediaSyncCron::HOOK, true ),
			$this->row( MediaSourceCleanupCron::HOOK, true ),
			$this->row( AccountStatsCron::HOOK, true ),
			$this->row( AccountTokenCron::HOOK, true ),
			$this->row( MediaQueueCron::HOOK, false ),
		];
	}

	/**
	 * @return array{hook:string,required:bool,scheduled:bool,next:string}
	 */
	private function row( string $hook, bool $required ): array {
		if ( ! function_exists( 'wp_next_scheduled' ) ) {
			return [
				'hook'      => $hook,
				'required'  => $required,
				'scheduled' => false,
				'next'      => '-',
			];
		}

		$next      = wp_next_scheduled( $hook );
		$nextLabel = $next ? date_i18n( 'Y-m-d H:i:s', (int) $next ) : '-';

		return [
			'hook'      => $hook,
			'required'  => $required,
			'scheduled' => (bool) $next,
			'next'      => $nextLabel,
		];
	}
}
