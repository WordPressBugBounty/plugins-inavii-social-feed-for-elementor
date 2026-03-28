<?php
declare(strict_types=1);

namespace Inavii\Instagram\RestApi\EndPoints\Settings;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Database\Database;
use Inavii\Instagram\FrontIndex\Application\FrontIndexService;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyDataMigrator;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyMigrationQueue;
use Inavii\Instagram\Settings\DeletePlatformData;
use Inavii\Instagram\Settings\GlobalSettingsService;
use WP_REST_Request;
use WP_REST_Response;

final class SettingsController {
	private GlobalSettingsService $settings;
	private DeletePlatformData $deleter;
	private FrontIndexService $frontIndex;
	private Database $database;
	private LegacyDataMigrator $legacyMigrator;
	private LegacyMigrationQueue $legacyMigrationQueue;

	public function __construct(
		GlobalSettingsService $settings,
		DeletePlatformData $deleter,
		FrontIndexService $frontIndex,
		Database $database,
		LegacyDataMigrator $legacyMigrator,
		LegacyMigrationQueue $legacyMigrationQueue
	) {
		$this->settings             = $settings;
		$this->deleter              = $deleter;
		$this->frontIndex           = $frontIndex;
		$this->database             = $database;
		$this->legacyMigrator       = $legacyMigrator;
		$this->legacyMigrationQueue = $legacyMigrationQueue;
	}

	public function get( WP_REST_Request $request ): WP_REST_Response {
		try {
			$payload                       = $this->settings->forApi();
			$payload['showV3EngineSwitch'] = $this->legacyMigrator->shouldBootstrapForUpgrade();

			return new WP_REST_Response( $payload, 200 );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		try {
			$params = $request->get_json_params();
			if ( ! is_array( $params ) ) {
				$params = $request->get_params();
			}

			$this->settings->update( is_array( $params ) ? $params : [] );

			return new WP_REST_Response( [ 'message' => 'Settings updated successfully' ], 200 );
		} catch ( \InvalidArgumentException $e ) {
			return new WP_REST_Response( [ 'error' => $e->getMessage() ], 400 );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function deleteAll( WP_REST_Request $request ): WP_REST_Response {
		try {
			$this->deleter->handle();

			return new WP_REST_Response( [ 'message' => 'All data has been deleted' ], 200 );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function clearCache( WP_REST_Request $request ): WP_REST_Response {
		try {
			$this->frontIndex->clearIndex();

			return new WP_REST_Response( [ 'message' => 'Cache has been cleared' ], 200 );
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function switchToV2( WP_REST_Request $request ): WP_REST_Response {
		try {
			$this->ensureLegacyMigrationContinuity();
			$storedUiVersion = Plugin::setUiVersion( 'v2' );

			return new WP_REST_Response(
				[
					'message'            => 'UI version switched to V2',
					'storedUiVersion'    => $storedUiVersion,
					'effectiveUiVersion' => Plugin::uiVersion(),
				],
				200
			);
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function switchToV3( WP_REST_Request $request ): WP_REST_Response {
		try {
			$this->ensureLegacyMigrationContinuity();
			$storedUiVersion = Plugin::setUiVersion( 'v3' );

			return new WP_REST_Response(
				[
					'message'            => 'UI version switched to V3',
					'storedUiVersion'    => $storedUiVersion,
					'effectiveUiVersion' => Plugin::uiVersion(),
				],
				200
			);
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	public function retryDatabase( WP_REST_Request $request ): WP_REST_Response {
		try {
			$result = $this->database->repair();

			if ( function_exists( 'delete_transient' ) ) {
				delete_transient( 'inavii_social_feed_health_issues' );
			}

			$rows = isset( $result['rows'] ) && is_array( $result['rows'] ) ? $result['rows'] : [];
			$failedRows = array_values(
				array_filter(
					$rows,
					static function ( $row ): bool {
						return is_array( $row ) && isset( $row['status'] ) && (string) $row['status'] !== 'ok';
					}
				)
			);

			if ( $failedRows === [] ) {
				return new WP_REST_Response(
					[
						'message' => 'Database repaired successfully',
						'rows'    => $rows,
					],
					200
				);
			}

			return new WP_REST_Response(
				[
					'message' => 'Database repair completed with errors',
					'rows'    => $rows,
					'failed'  => $failedRows,
				],
				200
			);
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	private function ensureLegacyMigrationContinuity(): void {
		if ( ! $this->legacyMigrator->shouldBootstrapForUpgrade() ) {
			return;
		}

		$this->legacyMigrator->maybeRunCritical();
		$this->legacyMigrationQueue->maybeScheduleFull();
	}

	private function unexpectedErrorResponse( \Throwable $e ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'error'   => 'Unexpected error',
				'message' => $e->getMessage(),
			],
			500
		);
	}
}
