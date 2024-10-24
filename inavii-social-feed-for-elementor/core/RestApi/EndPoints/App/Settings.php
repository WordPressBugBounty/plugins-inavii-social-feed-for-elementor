<?php

namespace Inavii\Instagram\RestApi\EndPoints\App;

use Inavii\Instagram\Media\Media;
use Inavii\Instagram\Utils\DeleteAllData;
use Inavii\Instagram\Utils\Utils;
use Inavii\Instagram\Utils\VersionChecker;
use Inavii\Instagram\Wp\ApiResponse;
use Inavii\Instagram\Wp\AppGlobalSettings;
use WP_REST_Request;
use WP_REST_Response;
class Settings {
    private $api;

    private $appGlobalSettings;

    public function __construct() {
        $this->api = new ApiResponse();
        $this->appGlobalSettings = new AppGlobalSettings();
    }

    public function settings() : WP_REST_Response {
        return $this->api->response( [
            'plans'                 => [
                'isEssentialsPlan' => VersionChecker::version()->is_plan( 'essentials' ) && VersionChecker::version()->is_premium() && VersionChecker::version()->can_use_premium_code(),
                'isProPlan'        => VersionChecker::version()->is_plan( 'premium' ) && VersionChecker::version()->is_premium() && VersionChecker::version()->can_use_premium_code(),
                'isUnlimitedPlan'  => VersionChecker::version()->is_plan( 'unlimited' ) && VersionChecker::version()->is_premium() && VersionChecker::version()->can_use_premium_code(),
            ],
            'gdLibraryAvailability' => Media::checkGDLibraryAvailability(),
            'timeZone'              => wp_timezone_string(),
            'globalSettings'        => [
                'cronInterval'          => $this->appGlobalSettings->getCronInterval(),
                'availableSchedules'    => $this->appGlobalSettings->getAvailableSchedules() ?? [],
                'numberOfPostsToImport' => $this->appGlobalSettings->getNumberOfPostsImported(),
                'emailNotifications'    => $this->appGlobalSettings->getEmailNotifications(),
                'email'                 => $this->appGlobalSettings->getEmail(),
                'renderOption'          => $this->appGlobalSettings->getRenderOption(),
            ],
            'pricingUrl'            => Utils::pricingPageLink(),
        ] );
    }

    public function deleteAllPlatformData() : WP_REST_Response {
        DeleteAllData::delete();
        return $this->api->response( [
            'message' => 'All data has been deleted',
        ] );
    }

    public function updateGlobalSettings( WP_REST_Request $request ) : WP_REST_Response {
        $data = $request->get_params();
        $cronInterval = ( isset( $data['cronInterval'] ) ? htmlspecialchars( $data['cronInterval'], ENT_QUOTES, 'UTF-8' ) : 'once-hourly' );
        $numberOfPostsToImport = filter_var( $data['numberOfPostsToImport'], FILTER_VALIDATE_INT, [
            'options' => [
                'default'   => 50,
                'min_range' => 1,
            ],
        ] );
        $emailNotifications = filter_var( $data['emailNotifications'], FILTER_VALIDATE_BOOLEAN );
        $email = sanitize_email( $data['email'] ) ?? '';
        $renderOption = filter_var( $data['renderOption'], FILTER_SANITIZE_SPECIAL_CHARS );
        $this->updateScheduledMediaUpdateTask( $cronInterval );
        $this->appGlobalSettings->saveCronInterval( $cronInterval );
        $this->appGlobalSettings->saveNumberOfPostsImported( $numberOfPostsToImport );
        $this->appGlobalSettings->saveEmailNotifications( $emailNotifications );
        $this->appGlobalSettings->saveRenderOption( $renderOption );
        return $this->api->response( [
            'message' => 'Settings updated successfully',
        ] );
    }

    private function updateScheduledMediaUpdateTask( $cronInterval ) {
        $timestamp = wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
        }
        if ( !wp_next_scheduled( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK ) ) {
            wp_schedule_event( time(), $cronInterval, AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );
        }
    }

}
