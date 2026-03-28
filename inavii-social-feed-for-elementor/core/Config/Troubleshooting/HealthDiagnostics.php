<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config\Troubleshooting;

use Inavii\Instagram\Config\Env;
use Inavii\Instagram\Media\Source\Storage\SourcesRepository;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Source\Domain\SourceSyncPolicy;

final class HealthDiagnostics {
	private const CACHE_KEY                   = 'inavii_social_feed_health_issues';
	private const RECONNECT_CACHE_KEY         = 'inavii_social_feed_reconnect_count';
	private const RECONNECT_CACHE_TTL_SECONDS = 300;

	private HookDiagnostics $hooks;
	private TableDiagnostics $tables;
	private CronDiagnostics $cron;
	private SourcesRepository $sources;
	private SourceSyncPolicy $policy;

	public function __construct(
		HookDiagnostics $hooks,
		TableDiagnostics $tables,
		CronDiagnostics $cron,
		SourcesRepository $sources,
		SourceSyncPolicy $policy
	) {
		$this->hooks   = $hooks;
		$this->tables  = $tables;
		$this->cron    = $cron;
		$this->sources = $sources;
		$this->policy  = $policy;
	}

	/**
	 * @return array
	 */
	public function issues( bool $useCache = true, bool $blockingOnly = false ): array {
		if ( $useCache && function_exists( 'get_transient' ) ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $blockingOnly ? $this->filterBlocking( $cached ) : $cached;
			}
		}

		$issues = $this->collectIssues();
		if ( $blockingOnly ) {
			return $this->filterBlocking( $issues );
		}

		if ( $useCache && function_exists( 'set_transient' ) ) {
			$ttl = (int) apply_filters( 'inavii/social-feed/health/cache_ttl', 900 );
			if ( $ttl > 0 ) {
				set_transient( self::CACHE_KEY, $issues, $ttl );
			}
		}

		return $issues;
	}

	/**
	 * @return array
	 */
	private function collectIssues(): array {
		$issues             = [];
		$tableStatus        = $this->tables->status();
		$sourcesTableExists = false;

		foreach ( $tableStatus as $row ) {
			if ( $row['label'] === 'sources' ) {
				$sourcesTableExists = (bool) $row['exists'];
			}

			if ( $row['exists'] ) {
				continue;
			}

			$details = 'The table ' . $row['table'] . ' is missing, so some features cannot work.';
			$error   = isset( $row['last_error'] ) ? trim( (string) $row['last_error'] ) : '';
			if ( $error !== '' ) {
				$details .= ' Last SQL error: ' . $this->shortText( $error, 220 ) . '.';
			}

			$this->addIssue(
				$issues,
				'table_missing:' . $row['label'],
				'error',
				'Database table missing: ' . $row['label'],
				$details,
				'Open Global Settings and run "Repair Database".||Open Global Settings|' . admin_url( 'admin.php?page=inavii-instagram-settings&screen=global_settings' ),
				true
			);
		}

		foreach ( $this->hooks->status() as $row ) {
			if ( ! $row['required'] || $row['has_listeners'] ) {
				continue;
			}

			$this->addIssue(
				$issues,
				'hook_missing:' . $row['hook'],
				'warning',
				'Hook has no listeners: ' . $row['label'],
				'The hook ' . $row['hook'] . ' has no listeners. This can break syncing or cleanup.',
				'Make sure the plugin is fully active and no autoload errors are present.',
				true
			);
		}

		foreach ( $this->cron->status() as $row ) {
			if ( ! $row['required'] || $row['scheduled'] ) {
				continue;
			}

			$this->addIssue(
				$issues,
				'cron_missing:' . $row['hook'],
				'warning',
				'Cron is not scheduled: ' . $row['hook'],
				'WordPress has no scheduled event for ' . $row['hook'] . '.',
				'Visit the plugin settings or re-save the configuration to reschedule cron.',
				true
			);
		}

		if ( $this->shouldReportCronRisk() ) {
			$staleAfterSeconds = $this->resolveCronStaleThreshold();
			$days              = max( 1, (int) floor( $staleAfterSeconds / $this->daySeconds() ) );
			$staleCount        = $this->countStaleSources( $staleAfterSeconds );

			if ( $staleCount > 0 ) {
				$this->addIssue(
					$issues,
					'cron_stale_sources',
					'warning',
					'Possible cron issue detected',
					sprintf(
						'%d active source(s) were not refreshed for at least %d day(s) and WP Cron is disabled.',
						$staleCount,
						$days
					),
					'Configure a real server cron that triggers wp-cron.php, or re-enable WP Cron.',
					true
				);
			}
		}

		$uploadError = '';
		if ( function_exists( 'wp_get_upload_dir' ) ) {
			$upload      = wp_get_upload_dir();
			$uploadError = isset( $upload['error'] ) ? (string) $upload['error'] : '';
		}

		if ( $uploadError !== '' ) {
			$this->addIssue(
				$issues,
				'uploads_error',
				'error',
				'Uploads directory error',
				'WordPress reports an uploads error: ' . $uploadError,
				'Fix the uploads directory path and permissions.',
				true
			);
		}

		if ( $this->shouldValidateMediaDirectory( $sourcesTableExists ) ) {
			if ( Env::$media_dir === '' ) {
				$this->addIssue(
					$issues,
					'uploads_missing',
					'error',
					'Uploads directory is not set',
					'Media directory path is empty, so files cannot be stored.',
					'Verify WordPress uploads configuration.',
					true
				);
			} elseif ( ! is_dir( Env::$media_dir ) ) {
				$this->addIssue(
					$issues,
					'uploads_not_found',
					'error',
					'Media directory does not exist',
					'The directory ' . Env::$media_dir . ' does not exist.',
					'Create the uploads directory or re-save plugin settings.',
					true
				);
			} elseif ( ! is_writable( Env::$media_dir ) ) {
				$this->addIssue(
					$issues,
					'uploads_not_writable',
					'error',
					'Media directory is not writable',
					'The directory ' . Env::$media_dir . ' is not writable.',
					'Fix filesystem permissions so WordPress can write media files.',
					true
				);
			}
		}

		$hasImageLib = function_exists( 'gd_info' ) || extension_loaded( 'imagick' );
		if ( ! $hasImageLib ) {
			$this->addIssue(
				$issues,
				'image_lib_missing',
				'error',
				'Image processing library missing',
				'Neither GD nor Imagick is available, so thumbnails cannot be generated.',
				'Enable GD or Imagick in PHP.',
				true
			);
		}

		$webpSupported = function_exists( 'imagewebp' );
		if ( ! $webpSupported && class_exists( '\Imagick' ) ) {
			try {
				$formats       = \Imagick::queryFormats( 'WEBP' );
				$webpSupported = is_array( $formats ) && $formats !== [];
			} catch ( \Throwable $e ) {
				$webpSupported = false;
			}
		}

		if ( ! $webpSupported ) {
			$this->addIssue(
				$issues,
				'webp_missing',
				'warning',
				'WebP not supported',
				'The server cannot generate WebP images.',
				'No action required. The plugin will automatically use JPG instead of WebP.',
				false
			);
		}

		$reconnectCount = $this->countReconnectRequiredAccounts();
		if ( $reconnectCount > 0 ) {
			$accountsUrl = admin_url( 'admin.php?page=inavii-instagram-settings&screen=accounts' );
			$this->addIssue(
				$issues,
				'accounts_reconnect_required',
				'error',
				'Accounts require reconnect',
				sprintf( '%d account(s) have authentication errors and cannot sync.', $reconnectCount ),
				'Reconnect the affected accounts in Settings → Accounts.||Open Accounts|' . $accountsUrl,
				true
			);
		}

		return $issues;
	}

	private function shouldValidateMediaDirectory( bool $sourcesTableExists ): bool {
		if ( ! $sourcesTableExists ) {
			return false;
		}

		try {
			return $this->sources->hasAnySources();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	private function shouldReportCronRisk(): bool {
		return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
	}

	private function resolveCronStaleThreshold(): int {
		return 3 * $this->daySeconds();
	}

	private function countStaleSources( int $staleAfterSeconds ): int {
		try {
			return $this->sources->countStaleActiveSources( $staleAfterSeconds );
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	private function daySeconds(): int {
		return defined( 'DAY_IN_SECONDS' ) ? (int) DAY_IN_SECONDS : 86400;
	}

	private function shortText( string $value, int $maxLen ): string {
		$value = trim( preg_replace( '/\s+/', ' ', $value ) ?? '' );
		if ( $value === '' ) {
			return '';
		}

		if ( strlen( $value ) <= $maxLen ) {
			return $value;
		}

		return substr( $value, 0, max( 1, $maxLen - 3 ) ) . '...';
	}

	public function countReconnectRequiredAccounts( bool $useCache = true ): int {
		if ( $useCache && function_exists( 'get_transient' ) ) {
			$cached = get_transient( self::RECONNECT_CACHE_KEY );
			if ( is_numeric( $cached ) ) {
				return max( 0, (int) $cached );
			}
		}

		$count = $this->queryReconnectRequiredAccounts();

		if ( $useCache && function_exists( 'set_transient' ) ) {
			set_transient( self::RECONNECT_CACHE_KEY, $count, self::RECONNECT_CACHE_TTL_SECONDS );
		}

		return $count;
	}

	public function flushReconnectRequiredAccountsCache(): void {
		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( self::RECONNECT_CACHE_KEY );
		}
	}

	private function queryReconnectRequiredAccounts(): int {
		try {
			$sources = $this->sources->getDisabledAccountSources();
		} catch ( \Throwable $e ) {
			return 0;
		}

		$count = 0;

		foreach ( $sources as $row ) {
			$kind = isset( $row['kind'] ) ? (string) $row['kind'] : '';
			if ( $kind !== Source::KIND_ACCOUNT ) {
				continue;
			}

			$message = isset( $row['last_error'] ) ? (string) $row['last_error'] : '';
			if ( $message !== '' && $this->policy->isAuthFailureMessage( $message ) ) {
				$count++;
			}
		}

		return $count;
	}

	private function addIssue( array &$issues, string $id, string $level, string $title, string $description, string $action, bool $blocking ): void {
		$issues[] = [
			'id'          => $id,
			'level'       => $level,
			'title'       => $title,
			'description' => $description,
			'action'      => $action,
			'blocking'    => $blocking,
		];
	}

	/**
	 * @param array $issues
	 * @return array
	 */
	private function filterBlocking( array $issues ): array {
		return array_values(
			array_filter(
				$issues,
				static function ( array $issue ): bool {
					return isset( $issue['blocking'] ) && $issue['blocking'];
				}
			)
		);
	}
}
