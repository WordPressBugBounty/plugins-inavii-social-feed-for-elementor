<?php

namespace Inavii\Instagram\SystemStatus;

use Inavii\Instagram\Wp\AppGlobalSettings;

class CronIssues
{
    public static function scheduleExists(): bool
    {
        $selectedSchedule = (new AppGlobalSettings())->getCronInterval();
        $availableSchedules = wp_get_schedules();

        if (!array_key_exists($selectedSchedule, $availableSchedules)) {
            return false;
        }

       return wp_next_scheduled(AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK);
    }

    public static function displayNotification(): void
    {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Inavii Social Feed</strong></p>
            <p>Your Instagram feeds cannot be auto-updated due to a missing cron schedule.</p>

            <p> Please go to the
                <a href="/wp-admin/admin.php?page=inavii-instagram-settings#/global-settings">
                    <strong>Inavii Social feed » Global Settings</strong>
                </a>
                page and set a new interval for how often checks for new Instagram posts.
            </p>
        </div>

        <?php
    }
}
