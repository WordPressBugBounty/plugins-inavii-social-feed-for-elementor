<?php

namespace Inavii\Instagram\Wp;

class AppGlobalSettings
{
    const CRON_SCHEDULE_UPDATE_MEDIA_TASK = 'inavii_social_feed_update_media';

    public function saveNumberOfPostsImported(int $numberOfPostsImported = 50): void
    {
        update_option('inavii_social_feed_number_of_posts_imported', $numberOfPostsImported);
    }

    public function getNumberOfPostsImported(): int
    {
        return (int)get_option('inavii_social_feed_number_of_posts_imported', 50);
    }

    public function saveCronInterval(string $cronInterval = 'hourly'): void
    {
        update_option('inavii_social_feed_cron_interval', $cronInterval);
    }

    public function getCronInterval(): string
    {
        return (string)get_option('inavii_social_feed_cron_interval', 'hourly');
    }

    public function saveEmailNotifications(bool $emailNotifications = false): void
    {
        update_option('inavii_social_feed_email_notifications', $emailNotifications);
    }

    public function getEmailNotifications(): bool
    {
        return (bool)get_option('inavii_social_feed_email_notifications', false);
    }

    public function getEmail(): string
    {
        $email = get_option('inavii_social_feed_email_to_notifications', false);

        if (!$email) {
            return get_option('admin_email', '');
        }

        return $email;
    }

    public function getRenderOption(): string
    {
        return get_option('inavii_social_feed_render_type', 'AJAX');
    }

    public function saveRenderOption($renderOption): void
    {
        update_option('inavii_social_feed_render_type', $renderOption);
    }

    public function saveEmail(string $email = ''): void
    {
        update_option('inavii_social_feed_email_to_notifications', $email);
    }

    public function getAvailableSchedules(): array
    {
        $schedules = wp_get_schedules();
        return array_filter($schedules, function ($schedule) {
            return $schedule['interval'] >= 900 && $schedule['interval'] <= 604800;
        });
    }
}