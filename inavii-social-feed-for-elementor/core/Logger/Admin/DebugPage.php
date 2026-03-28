<?php
declare(strict_types=1);

namespace Inavii\Instagram\Logger\Admin;

use Inavii\Instagram\Account\Cron\AccountStatsCron;
use Inavii\Instagram\Account\Cron\AccountTokenCron;
use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Logger\Storage\LoggerRepository;
use Inavii\Instagram\Logger\Logger;
use Inavii\Instagram\Media\Cron\MediaQueueCron;
use Inavii\Instagram\Media\Cron\MediaSourceCleanupCron;
use Inavii\Instagram\Media\Cron\MediaSyncCron;
use Inavii\Instagram\Config\Troubleshooting\HealthDiagnostics;
use Inavii\Instagram\Config\Troubleshooting\HookDiagnostics;
use Inavii\Instagram\Config\Troubleshooting\TableDiagnostics;
use Inavii\Instagram\Config\Troubleshooting\CronDiagnostics;
use function Inavii\Instagram\Di\container;

final class DebugPage {

	private const QUERY_FLAG = 'inavii-debug';

	public function __construct() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'handleActions' ] );
	}

	public function handleActions(): void {
		if ( ! $this->isPage() || ! self::isEnabled() ) {
			return;
		}

		if ( self::isTroubleshootingView() && isset( $_GET['inavii_health_refresh'] ) ) {
			delete_transient( 'inavii_social_feed_health_issues' );
		}

		$action = isset( $_POST['inavii_logs_action'] ) ? sanitize_key( (string) $_POST['inavii_logs_action'] ) : '';
		if ( $action === '' ) {
			return;
		}

		check_admin_referer( 'inavii_logs_action' );

		if ( $action === 'clear' ) {
			Logger::clear();
			$args = [ 'inavii_logs_status' => 'cleared' ];
			if ( self::isTroubleshootingView() ) {
				$args['inavii_troubleshooting'] = '1';
			}
			$url = add_query_arg( $args, self::debugUrl() );
			wp_safe_redirect( $url );
			exit;
		}

		if ( $action === 'export_csv' ) {
			$this->exportCsv();
			exit;
		}
	}

	public function render(): void {
		self::renderView();
	}

	public static function renderView(): void {
		if ( ! self::isEnabled() ) {
			echo '<div class="wrap"><h1>Inavii Debug</h1><p>Debug view is disabled.</p></div>';
			return;
		}

		$isTroubleshooting = self::isTroubleshootingView();
		$limit             = (int) apply_filters( 'inavii/social-feed/logs/max_entries', 100 );
		$limit             = max( 1, $limit );

		$rows   = Logger::latest( $limit );
		$status = isset( $_GET['inavii_logs_status'] ) ? sanitize_key( (string) $_GET['inavii_logs_status'] ) : '';

		echo '<div class="wrap inavii-debug-wrap">';
		echo '<h1>' . ( $isTroubleshooting ? 'Inavii Troubleshooting' : 'Inavii Debug Logs' ) . '</h1>';
		echo '<p class="description">Latest ' . esc_html( (string) $limit ) . ' entries (most recent first).</p>';

		if ( $isTroubleshooting ) {
			self::renderTroubleshootingSummary();
		} else {
			self::renderSummary();
		}

		if ( $status === 'cleared' ) {
			echo '<div class="notice notice-success"><p>Logs cleared.</p></div>';
		}

		echo '<div class="inavii-debug-actions">';
		echo '<form method="post" style="display:inline-block;margin-right:10px;">';
		wp_nonce_field( 'inavii_logs_action' );
		echo '<input type="hidden" name="inavii_logs_action" value="clear">';
		echo '<button class="button button-secondary" type="submit">Clear Logs</button>';
		echo '</form>';

		echo '<form method="post" style="display:inline-block;margin-right:10px;">';
		wp_nonce_field( 'inavii_logs_action' );
		echo '<input type="hidden" name="inavii_logs_action" value="export_csv">';
		echo '<button class="button button-secondary" type="submit">Export CSV</button>';
		echo '</form>';

		echo '<button class="button button-secondary" type="button" id="inavii-debug-copy">Copy Logs</button>';

		echo '</div>';

		self::renderLogsTable( $rows );

		$copyText = self::formatCopyText( $rows );
		echo '<textarea id="inavii-debug-copy-area" readonly style="position:absolute;left:-9999px;top:-9999px;">' . esc_textarea( $copyText ) . '</textarea>';
		echo '<div id="inavii-debug-copy-status" style="margin-top:8px;color:#065f46;display:none;">Logs copied.</div>';
		echo '</div>';

		self::renderStyles();
	}

	private static function renderSummary(): void {
		$repo        = self::repo();
		$sourceStats = $repo ? $repo->sourcesStats() : [
			'total'    => 0,
			'active'   => 0,
			'disabled' => 0,
			'pinned'   => 0,
			'due'      => 0,
		];
		$mediaStats  = $repo ? $repo->mediaStats() : [
			'total'    => 0,
			'queued'   => 0,
			'ready'    => 0,
			'failed'   => 0,
			'children' => 0,
		];
		$hooks       = self::hookStats();
		$tables      = self::tableStats();
		$cronHealth  = self::cronHealthStats();
		$issues      = self::healthIssues();

		echo '<div class="inavii-debug-summary">';
		echo '<div class="inavii-debug-card">';
		echo '<h2>Environment</h2>';
		echo '<ul>';
		echo '<li><strong>Plugin version:</strong> ' . esc_html( Plugin::version() ) . '</li>';
		echo '<li><strong>WordPress:</strong> ' . esc_html( (string) get_bloginfo( 'version' ) ) . '</li>';
		echo '<li><strong>PHP:</strong> ' . esc_html( (string) PHP_VERSION ) . '</li>';
		echo '<li><strong>WP Cron:</strong> ' . ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'disabled' : 'enabled' ) . '</li>';
		echo '</ul>';
		echo '</div>';

		echo '<div class="inavii-debug-card">';
		echo '<h2>Sources</h2>';
		echo '<ul>';
		echo '<li><strong>Total:</strong> ' . esc_html( (string) $sourceStats['total'] ) . '</li>';
		echo '<li><strong>Active:</strong> ' . esc_html( (string) $sourceStats['active'] ) . '</li>';
		echo '<li><strong>Disabled:</strong> ' . esc_html( (string) $sourceStats['disabled'] ) . '</li>';
		echo '<li><strong>Pinned:</strong> ' . esc_html( (string) $sourceStats['pinned'] ) . '</li>';
		echo '<li><strong>Due to sync:</strong> ' . esc_html( (string) $sourceStats['due'] ) . '</li>';
		echo '</ul>';
		echo '</div>';

		echo '<div class="inavii-debug-card">';
		echo '<h2>Media</h2>';
		echo '<ul>';
		echo '<li><strong>Total:</strong> ' . esc_html( (string) $mediaStats['total'] ) . '</li>';
		echo '<li><strong>Files queued:</strong> ' . esc_html( (string) $mediaStats['queued'] ) . '</li>';
		echo '<li><strong>Files ready:</strong> ' . esc_html( (string) $mediaStats['ready'] ) . '</li>';
		echo '<li><strong>Files failed:</strong> ' . esc_html( (string) $mediaStats['failed'] ) . '</li>';
		echo '<li><strong>Children:</strong> ' . esc_html( (string) $mediaStats['children'] ) . '</li>';
		echo '</ul>';
		echo '</div>';
		echo '</div>';

		echo '<div class="inavii-debug-card inavii-debug-card--wide">';
		echo '<h2>Troubleshooting Issues</h2>';
		if ( $issues === [] ) {
			echo '<p>No issues detected.</p>';
		} else {
			echo '<table class="widefat fixed striped">';
			echo '<thead><tr>';
			echo '<th style="width:120px;">Level</th><th>Issue</th><th>Details</th><th style="width:260px;">How to fix</th>';
			echo '</tr></thead><tbody>';
			foreach ( $issues as $issue ) {
				$level = isset( $issue['level'] ) ? (string) $issue['level'] : 'warning';
				echo '<tr>';
				echo '<td><span class="inavii-status inavii-status-' . esc_attr( $level ) . '">' . esc_html( $level ) . '</span></td>';
				echo '<td>' . esc_html( (string) ( $issue['title'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( (string) ( $issue['description'] ?? '' ) ) . '</td>';
				echo '<td>' . wp_kses_post( self::formatActionCell( (string) ( $issue['action'] ?? '' ) ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<h3 class="inavii-debug-support-title">Need help?</h3>';
			echo '<div class="inavii-debug-support">Contact <a href="mailto:support@inavii.com">support@inavii.com</a> or <a href="' . esc_url( admin_url( 'admin.php?page=inavii-instagram-settings-contact' ) ) . '">open the support page</a>.</div>';
		}
		echo '</div>';

		echo '<div class="inavii-debug-card inavii-debug-card--wide">';
		echo '<h2>Hook Health</h2>';
		echo '<table class="widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>Hook</th><th>Label</th><th>Required</th><th>Status</th>';
		echo '</tr></thead><tbody>';

		if ( $hooks === [] ) {
			echo '<tr><td colspan="3">No hook diagnostics configured.</td></tr>';
		} else {
			foreach ( $hooks as $row ) {
				$status   = $row['has_listeners'] ? 'ok' : 'missing';
				$required = isset( $row['required'] ) && $row['required'] ? 'yes' : 'no';
				echo '<tr>';
				echo '<td>' . esc_html( $row['hook'] ) . '</td>';
				echo '<td>' . esc_html( $row['label'] ) . '</td>';
				echo '<td>' . esc_html( $required ) . '</td>';
				echo '<td><span class="inavii-status inavii-status-' . esc_attr( $status ) . '">' . esc_html( $status ) . '</span></td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table>';
		echo '</div>';

		echo '<div class="inavii-debug-card inavii-debug-card--wide">';
		echo '<h2>Table Health</h2>';
		echo '<table class="widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>Label</th><th>Table</th><th>Status</th><th>Exists</th><th>Updated</th><th>Details</th>';
		echo '</tr></thead><tbody>';

		if ( $tables === [] ) {
			echo '<tr><td colspan="6">No table diagnostics configured.</td></tr>';
		} else {
			foreach ( $tables as $row ) {
				$status = self::tableHealthStatus( $row );
				echo '<tr>';
				echo '<td>' . esc_html( $row['label'] ) . '</td>';
				echo '<td>' . esc_html( $row['table'] ) . '</td>';
				echo '<td><span class="inavii-status inavii-status-' . esc_attr( $status ) . '">' . esc_html( $status ) . '</span></td>';
				echo '<td>' . esc_html( ! empty( $row['exists'] ) ? 'yes' : 'no' ) . '</td>';
				echo '<td>' . esc_html( (string) ( $row['updated_at'] ?? '-' ) ) . '</td>';
				echo '<td>' . self::formatTableHealthDetails( $row ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table>';
		echo '</div>';

		echo '<div class="inavii-debug-card inavii-debug-card--wide">';
		echo '<h2>Cron Health</h2>';
		echo '<table class="widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>Hook</th><th>Required</th><th>Status</th><th>Next</th><th>Last run</th><th>Last success</th>';
		echo '</tr></thead><tbody>';

		if ( $cronHealth === [] ) {
			echo '<tr><td colspan="6">No cron diagnostics configured.</td></tr>';
		} else {
			foreach ( $cronHealth as $row ) {
				$required = $row['required'] ? 'yes' : 'no';
				$status   = $row['scheduled'] ? 'ok' : 'missing';
				echo '<tr>';
				echo '<td>' . esc_html( $row['hook'] ) . '</td>';
				echo '<td>' . esc_html( $required ) . '</td>';
				echo '<td><span class="inavii-status inavii-status-' . esc_attr( $status ) . '">' . esc_html( $status ) . '</span></td>';
				echo '<td>' . esc_html( $row['next'] ) . '</td>';
				echo '<td>' . esc_html( $row['last_run'] ) . '</td>';
				echo '<td>' . esc_html( $row['last_success'] ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	private static function renderTroubleshootingSummary(): void {
		$issues = self::healthIssues( true );

		echo '<div class="inavii-debug-card inavii-debug-card--wide">';
		echo '<div class="inavii-debug-card-header">';
		echo '<h2>Detected Issues</h2>';
		if ( self::isTroubleshootingView() ) {
			$refreshArgs = [
				'page'                   => 'inavii-instagram-settings-debug',
				'inavii_troubleshooting' => '1',
				'inavii_health_refresh'  => '1',
			];
			$refreshUrl  = add_query_arg( $refreshArgs, admin_url( 'admin.php' ) );
			echo '<a class="button button-secondary" href="' . esc_url( $refreshUrl ) . '">Check again</a>';
		}
		echo '</div>';
		if ( $issues === [] ) {
			echo '<p>No issues detected.</p>';
		} else {
			echo '<table class="widefat fixed striped">';
			echo '<thead><tr>';
			echo '<th style="width:120px;">Level</th><th>Issue</th><th>Details</th><th style="width:260px;">How to fix</th>';
			echo '</tr></thead><tbody>';
			foreach ( $issues as $issue ) {
				$level = isset( $issue['level'] ) ? (string) $issue['level'] : 'warning';
				echo '<tr>';
				echo '<td><span class="inavii-status inavii-status-' . esc_attr( $level ) . '">' . esc_html( $level ) . '</span></td>';
				echo '<td>' . esc_html( (string) ( $issue['title'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( (string) ( $issue['description'] ?? '' ) ) . '</td>';
				echo '<td>' . wp_kses_post( self::formatActionCell( (string) ( $issue['action'] ?? '' ) ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<h3 class="inavii-debug-support-title">Need help?</h3>';
			echo '<div class="inavii-debug-support">Contact <a href="mailto:support@inavii.com">support@inavii.com</a> or <a href="' . esc_url( admin_url( 'admin.php?page=inavii-instagram-settings-contact' ) ) . '">open the support page</a>.</div>';
		}
		echo '</div>';
	}

	/**
	 * @param array $rows
	 */
	private static function renderLogsTable( array $rows ): void {
		echo '<table class="widefat fixed striped inavii-debug-table">';
		echo '<thead><tr>';
		echo '<th style="width:160px;">Time</th>';
		echo '<th style="width:90px;">Level</th>';
		echo '<th style="width:160px;">Component</th>';
		echo '<th>Message</th>';
		echo '<th style="width:260px;">Context</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		if ( $rows === [] ) {
			echo '<tr><td colspan="5">No log entries.</td></tr>';
		} else {
			foreach ( $rows as $row ) {
				$level     = isset( $row['level'] ) ? (string) $row['level'] : '';
				$component = isset( $row['component'] ) ? (string) $row['component'] : '';
				$message   = isset( $row['message'] ) ? (string) $row['message'] : '';
				$created   = isset( $row['created_at'] ) ? (string) $row['created_at'] : '';
				$context   = isset( $row['context_json'] ) ? (string) $row['context_json'] : '';

				echo '<tr>';
				echo '<td>' . esc_html( $created ) . '</td>';
				echo '<td><span class="inavii-debug-level inavii-debug-level-' . esc_attr( $level ) . '">' . esc_html( $level ) . '</span></td>';
				echo '<td>' . esc_html( $component ) . '</td>';
				echo '<td>' . esc_html( $message ) . '</td>';
				echo '<td>';
				if ( $context !== '' ) {
					echo '<details><summary>Show</summary><pre>' . esc_html( $context ) . '</pre></details>';
				} else {
					echo '-';
				}
				echo '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table>';
	}

	private static function renderStyles(): void {
		echo '<style>
            .inavii-debug-wrap .inavii-debug-actions { margin: 16px 0; }
            .inavii-debug-table pre { white-space: pre-wrap; max-width: 520px; }
            .inavii-debug-level { padding: 2px 8px; border-radius: 12px; font-size: 12px; text-transform: uppercase; }
            .inavii-debug-level-error { background: #fbe9e7; color: #d84315; }
            .inavii-debug-level-warning { background: #fff8e1; color: #f57f17; }
            .inavii-debug-level-info { background: #e3f2fd; color: #1565c0; }
            .inavii-debug-level-debug { background: #eceff1; color: #455a64; }
            .inavii-status { padding: 2px 10px; border-radius: 12px; font-size: 12px; text-transform: uppercase; display: inline-block; }
            .inavii-status-ok { background: #e8f5e9; color: #1b5e20; }
            .inavii-status-missing { background: #ffebee; color: #b71c1c; }
            .inavii-status-warning { background: #fff8e1; color: #a16207; }
            .inavii-status-error { background: #ffebee; color: #b71c1c; }
            .inavii-debug-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 20px 0; }
            .inavii-debug-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; }
            .inavii-debug-card h2 { margin: 0 0 10px; font-size: 16px; }
            .inavii-debug-card-header { display: flex; align-items: center; justify-content: flex-start; gap: 12px; margin-bottom: 10px; }
            .inavii-debug-card-header h2 { margin: 0; }
            .inavii-debug-card ul { margin: 0; padding-left: 16px; }
            .inavii-debug-card--wide { margin-top: 16px; }
            .inavii-debug-code { margin: 8px 0 0; padding: 8px 10px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 12px; line-height: 1.35; white-space: pre-wrap; word-break: break-word; }
            .inavii-debug-support-title { margin: 14px 0 6px; font-size: 14px; }
            .inavii-debug-support { padding: 10px 12px; border-radius: 0; font-size: 13px; background: #f8fafc; border: 1px solid #e5e7eb; }
            .inavii-debug-support a { color: #7a3cff; font-weight: 600; text-decoration: none; }
            .inavii-debug-support a:hover { text-decoration: underline; }
            @media (max-width: 1200px) { .inavii-debug-summary { grid-template-columns: 1fr; } }
        </style>';

		echo '<script>
            (function(){
                var btn = document.getElementById("inavii-debug-copy");
                if(!btn){ return; }
                btn.addEventListener("click", function(){
                    var area = document.getElementById("inavii-debug-copy-area");
                    var status = document.getElementById("inavii-debug-copy-status");
                    if(!area){ return; }
                    area.style.display = "block";
                    area.select();
                    area.setSelectionRange(0, area.value.length);
                    try {
                        document.execCommand("copy");
                        if(status){ status.style.display = "block"; }
                    } catch(e){}
                    area.style.display = "none";
                });
            })();
        </script>';
	}

	private static function repo(): ?LoggerRepository {
		if ( ! function_exists( 'Inavii\\Instagram\\Di\\container' ) ) {
			return null;
		}

		try {
			return container()->get( LoggerRepository::class );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * @return array
	 */
	private static function hookStats(): array {
		if ( ! function_exists( 'Inavii\\Instagram\\Di\\container' ) ) {
			return [];
		}

		try {
			/** @var HookDiagnostics $diag */
			$diag = container()->get( HookDiagnostics::class );
		} catch ( \Throwable $e ) {
			return [];
		}

		return $diag->status();
	}

	/**
	 * @return array
	 */
	private static function tableStats(): array {
		if ( ! function_exists( 'Inavii\\Instagram\\Di\\container' ) ) {
			return [];
		}

		try {
			/** @var TableDiagnostics $diag */
			$diag = container()->get( TableDiagnostics::class );
		} catch ( \Throwable $e ) {
			return [];
		}

		return $diag->status();
	}

	/**
	 * @return array
	 */
	private static function healthIssues( bool $blockingOnly = false ): array {
		if ( ! function_exists( 'Inavii\\Instagram\\Di\\container' ) ) {
			return [];
		}

		try {
			/** @var HealthDiagnostics $diag */
			$diag = container()->get( HealthDiagnostics::class );
		} catch ( \Throwable $e ) {
			return [];
		}

		return $diag->issues( false, $blockingOnly );
	}

	/**
	 * @return array
	 */
	private static function cronHealthStats(): array {
		if ( ! function_exists( 'Inavii\\Instagram\\Di\\container' ) ) {
			return [];
		}

		try {
			/** @var CronDiagnostics $diag */
			$diag = container()->get( CronDiagnostics::class );
		} catch ( \Throwable $e ) {
			return [];
		}

		$rows = [];
		foreach ( $diag->status() as $row ) {
			$hook      = isset( $row['hook'] ) ? (string) $row['hook'] : '';
			$required  = isset( $row['required'] ) && $row['required'];
			$scheduled = isset( $row['scheduled'] ) && $row['scheduled'];
			$next      = isset( $row['next'] ) ? (string) $row['next'] : '-';

			$lastRunOption     = '';
			$lastSuccessOption = '';
			if ( $hook === MediaSyncCron::HOOK ) {
				$lastRunOption     = MediaSyncCron::LAST_RUN_OPTION;
				$lastSuccessOption = MediaSyncCron::LAST_SUCCESS_OPTION;
			} elseif ( $hook === AccountStatsCron::HOOK ) {
				$lastRunOption     = AccountStatsCron::LAST_RUN_OPTION;
				$lastSuccessOption = AccountStatsCron::LAST_SUCCESS_OPTION;
			} elseif ( $hook === AccountTokenCron::HOOK ) {
				$lastRunOption     = AccountTokenCron::LAST_RUN_OPTION;
				$lastSuccessOption = AccountTokenCron::LAST_SUCCESS_OPTION;
			}

			$lastRun     = $lastRunOption !== '' ? (int) get_option( $lastRunOption, 0 ) : 0;
			$lastSuccess = $lastSuccessOption !== '' ? (int) get_option( $lastSuccessOption, 0 ) : 0;

			$rows[] = [
				'hook'         => $hook,
				'required'     => $required,
				'scheduled'    => $scheduled,
				'next'         => $next,
				'last_run'     => $lastRun > 0 ? self::formatTimestamp( $lastRun ) : '-',
				'last_success' => $lastSuccess > 0 ? self::formatTimestamp( $lastSuccess ) : '-',
			];
		}

		return $rows;
	}

	private static function formatTimestamp( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return '-';
		}

		return date_i18n( 'Y-m-d H:i:s', $timestamp );
	}

	private static function formatActionCell( string $action ): string {
		if ( $action === '' ) {
			return '-';
		}

		if ( strpos( $action, '||' ) !== false ) {
			[$text, $code] = array_pad( explode( '||', $action, 2 ), 2, '' );
			$html          = '<div>' . esc_html( $text ) . '</div>';
			if ( $code !== '' ) {
				if ( strpos( $code, '|' ) !== false ) {
					[$label, $url] = array_pad( explode( '|', $code, 2 ), 2, '' );
					if ( $url !== '' ) {
						$html .= '<div class="inavii-debug-action-link"><a class="button button-secondary" href="' . esc_url( $url ) . '">' . esc_html( $label !== '' ? $label : 'Open' ) . '</a></div>';
						return $html;
					}
				}
				$html .= '<pre class="inavii-debug-code"><code>' . esc_html( $code ) . '</code></pre>';
			}
			return $html;
		}

		return esc_html( $action );
	}

	/**
	 * @param array<string,mixed> $row
	 */
	private static function tableHealthStatus( array $row ): string {
		$status = sanitize_key( (string) ( $row['status'] ?? '' ) );
		if ( in_array( $status, [ 'ok', 'missing', 'warning', 'error' ], true ) ) {
			return $status;
		}

		return ! empty( $row['exists'] ) ? 'ok' : 'missing';
	}

	/**
	 * @param array<string,mixed> $row
	 */
	private static function formatTableHealthDetails( array $row ): string {
		$error = trim( (string) ( $row['last_error'] ?? '' ) );
		$query = trim( (string) ( $row['last_query'] ?? '' ) );

		if ( $error === '' && $query === '' ) {
			return '-';
		}

		$html = '';
		if ( $error !== '' ) {
			$html .= '<div>' . esc_html( $error ) . '</div>';
		}

		if ( $query !== '' ) {
			$html .= '<details><summary>Last SQL</summary><pre class="inavii-debug-code"><code>' . esc_html( $query ) . '</code></pre></details>';
		}

		return $html !== '' ? $html : '-';
	}

	private function exportCsv(): void {
		$limit = (int) apply_filters( 'inavii/social-feed/logs/max_entries', 100 );
		$limit = max( 1, $limit );
		$rows  = Logger::latest( $limit );

		$filename = 'inavii-debug-logs-' . gmdate( 'Ymd-His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$out = fopen( 'php://output', 'w' );
		if ( ! $out ) {
			return;
		}

		fputcsv( $out, [ 'time', 'level', 'component', 'message', 'context_json' ] );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				[
					(string) ( $row['created_at'] ?? '' ),
					(string) ( $row['level'] ?? '' ),
					(string) ( $row['component'] ?? '' ),
					(string) ( $row['message'] ?? '' ),
					(string) ( $row['context_json'] ?? '' ),
				]
			);
		}

		fclose( $out );
	}

	/**
	 * @param array $rows
	 */
	private static function formatCopyText( array $rows ): string {
		$lines = [];
		foreach ( $rows as $row ) {
			$lines[] = sprintf(
				'[%s] [%s] [%s] %s %s',
				(string) ( $row['created_at'] ?? '' ),
				(string) ( $row['level'] ?? '' ),
				(string) ( $row['component'] ?? '' ),
				(string) ( $row['message'] ?? '' ),
				(string) ( $row['context_json'] ?? '' )
			);
		}

		return implode( "\n", $lines );
	}

	private function isPage(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : '';
		return in_array( $page, [ 'inavii-instagram-settings', 'inavii-instagram-settings-debug' ], true );
	}

	public static function isEnabled(): bool {
		$raw = null;
		if ( isset( $_GET[ self::QUERY_FLAG ] ) ) {
			$raw = $_GET[ self::QUERY_FLAG ];
		} elseif ( isset( $_GET['inavii_debug'] ) ) {
			$raw = $_GET['inavii_debug'];
		} elseif ( isset( $_REQUEST[ self::QUERY_FLAG ] ) ) {
			$raw = $_REQUEST[ self::QUERY_FLAG ];
		} elseif ( isset( $_SERVER['QUERY_STRING'] ) && is_string( $_SERVER['QUERY_STRING'] ) ) {
			if ( strpos( $_SERVER['QUERY_STRING'], self::QUERY_FLAG . '=1' ) !== false ) {
				$raw = '1';
			}
		}

		$flag          = is_scalar( $raw ) ? sanitize_text_field( (string) $raw ) : '';
		$paramEnabled  = in_array( $flag, [ '1', 'true', 'yes', 'on' ], true );
		$page          = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : '';
		$pageEnabled   = $page === 'inavii-instagram-settings-debug';
		$filterEnabled = (bool) apply_filters( 'inavii/social-feed/logs/debug_view_enabled', false );

		return $paramEnabled || $pageEnabled || $filterEnabled;
	}

	private static function isTroubleshootingView(): bool {
		$flag = '';
		if ( isset( $_GET['inavii_troubleshooting'] ) ) {
			$flag = sanitize_text_field( (string) $_GET['inavii_troubleshooting'] );
		}

		return in_array( $flag, [ '1', 'true', 'yes', 'on' ], true );
	}

	public static function debugUrl(): string {
		return admin_url( 'admin.php?page=inavii-instagram-settings-debug' );
	}
}
