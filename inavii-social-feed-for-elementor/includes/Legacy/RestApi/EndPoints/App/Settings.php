<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\App;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyDataMigrator;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyMigrationQueue;
use Inavii\Instagram\Settings\DeletePlatformData;
use Inavii\Instagram\Settings\PricingPage;
use Inavii\Instagram\Freemius\FreemiusAccess;
use Inavii\Instagram\Wp\ApiResponse;
use Inavii\Instagram\Wp\AppGlobalSettings;
use WP_REST_Request;
use WP_REST_Response;

final class Settings {
	private AppGlobalSettings $settings;
	private DeletePlatformData $deleter;
	private ApiResponse $api;
	private LegacyDataMigrator $legacyMigrator;
	private LegacyMigrationQueue $legacyMigrationQueue;

	public function __construct(
		AppGlobalSettings $settings,
		DeletePlatformData $deleter,
		ApiResponse $api,
		LegacyDataMigrator $legacyMigrator,
		LegacyMigrationQueue $legacyMigrationQueue
	) {
		$this->settings             = $settings;
		$this->deleter              = $deleter;
		$this->api                  = $api;
		$this->legacyMigrator       = $legacyMigrator;
		$this->legacyMigrationQueue = $legacyMigrationQueue;
	}

	public function settings(): WP_REST_Response {
		try {
			$version = FreemiusAccess::version();

			return $this->api->response(
				[
					'isPro'                 => FreemiusAccess::canUsePremiumCode(),
					'plans'                 => $this->resolvePlans(),
					'gdLibraryAvailability' => $this->checkGDLibraryAvailability(),
					'timeZone'              => wp_timezone_string(),
					'globalSettings'        => [
						'cronInterval'         => $this->settings->getCronInterval(),
						'availableSchedules'   => $this->settings->getAvailableSchedules(),
						'numberOfPostsToImport' => $this->settings->getNumberOfPostsImported(),
						'emailNotifications'   => $this->settings->getEmailNotifications(),
						'email'                => $this->settings->getEmail(),
						'renderOption'         => $this->settings->getRenderOption(),
					],
					'pricingUrl'            => PricingPage::url(),
					'uiVersion'             => Plugin::uiVersion(),
					'errorLog'              => [],
				]
			);
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	public function switchToV3(): WP_REST_Response {
		try {
			$this->ensureLegacyMigrationContinuity();
			$storedUiVersion = Plugin::setUiVersion( 'v3' );

			return $this->api->response(
				[
					'message'         => 'UI version switched to V3',
					'storedUiVersion' => $storedUiVersion,
					'effectiveUiVersion' => Plugin::uiVersion(),
				]
			);
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	public function deleteAllPlatformData(): WP_REST_Response {
		try {
			$this->deleter->handle();

			return $this->api->response( [ 'message' => 'All data has been deleted' ] );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	public function updateGlobalSettings( WP_REST_Request $request ): WP_REST_Response {
		try {
			$params = $request->get_json_params();
			if ( ! is_array( $params ) ) {
				$params = $request->get_params();
			}

			$cronInterval = isset( $params['cronInterval'] ) ? sanitize_text_field( (string) $params['cronInterval'] ) : 'hourly';
			$cronInterval = $this->normalizeSchedule( $cronInterval );
			$importLimit  = isset( $params['numberOfPostsToImport'] ) ? (int) $params['numberOfPostsToImport'] : 50;
			$importLimit  = max( 1, $importLimit );

			$emailNotifications = isset( $params['emailNotifications'] )
				? (bool) filter_var( $params['emailNotifications'], FILTER_VALIDATE_BOOLEAN )
				: false;
			$email              = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';
			$renderOption       = isset( $params['renderOption'] ) ? strtoupper( sanitize_text_field( (string) $params['renderOption'] ) ) : 'AJAX';
			if ( $renderOption !== 'AJAX' && $renderOption !== 'PHP' ) {
				$renderOption = 'AJAX';
			}

			if ( FreemiusAccess::canUsePremiumCode() && ! is_email( $email ) ) {
				return $this->api->response( [ 'errors' => 'Invalid email address' ], false );
			}

			$this->updateScheduledMediaUpdateTask( $cronInterval );
			$this->settings->saveCronInterval( $cronInterval );
			$this->settings->saveNumberOfPostsImported( $importLimit );
			$this->settings->saveEmailNotifications( $emailNotifications );
			$this->settings->saveRenderOption( $renderOption );
			if ( FreemiusAccess::canUsePremiumCode() ) {
				$this->settings->saveEmail( $email );
			}

			return $this->api->response( [ 'message' => 'Settings updated successfully' ] );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, 'Unexpected error: ' . $e->getMessage() );
		}
	}

	private function checkGDLibraryAvailability(): bool {
		return function_exists( 'gd_info' ) && extension_loaded( 'gd' );
	}

	private function ensureLegacyMigrationContinuity(): void {
		if ( ! $this->legacyMigrator->shouldBootstrapForUpgrade() ) {
			return;
		}

		$this->legacyMigrator->maybeRunCritical();
		$this->legacyMigrationQueue->maybeScheduleFull();
	}

	private function resolvePlans(): array {
		$version = FreemiusAccess::version();
		$allowed = FreemiusAccess::canUsePremiumCode();

		return [
			'isEssentialsPlan' => $version->is_plan( 'essentials' ) && $allowed,
			'isProPlan'        => $version->is_plan( 'premium' ) && $allowed,
			'isUnlimitedPlan'  => $version->is_plan( 'unlimited' ) && $allowed,
		];
	}

	private function normalizeSchedule( string $schedule ): string {
		$schedules = wp_get_schedules();
		if ( isset( $schedules[ $schedule ] ) ) {
			return $schedule;
		}

		return 'hourly';
	}

	private function updateScheduledMediaUpdateTask( string $cronInterval ): void {
		$timestamp = wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
		}

		if ( ! wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK ) ) {
			wp_schedule_event( time(), $cronInterval, AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
		}
	}
}
