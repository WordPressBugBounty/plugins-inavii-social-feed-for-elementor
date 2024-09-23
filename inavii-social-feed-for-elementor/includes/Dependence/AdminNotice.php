<?php

namespace Inavii\Instagram\Includes\Dependence;

use Inavii\Instagram\SystemStatus\CronIssues;
use Inavii\Instagram\SystemStatus\TokenIssues;

class AdminNotice
{
    public function __construct()
    {
        if (!defined('ELEMENTOR_VERSION')) {
            return;
        }
        // Check if Elementor installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'adminNoticeMissingElementorPlugin'));

            return;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, INAVII_SOCIAL_FEED_E_MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'adminNoticeMinimumElementorVersion'));

            return;
        }

        // Check for required PHP version
        if (version_compare(PHP_VERSION, INAVII_SOCIAL_FEED_E_MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'adminNoticeMinimumPhpVersion'));

            return;
        }

        add_action('admin_notices', array($this, 'cronIssues'));
        add_action('admin_notices', array($this, 'instagramTokenIssues'));
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have Elementor installed or activated.
     */
    public function adminNoticeMissingElementorPlugin(): void
    {
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'inavii-social-feed-e'),
            '<strong>' . esc_html__('Inavii Social Feed', 'inavii-social-feed-e') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'inavii-social-feed-e') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required Elementor version.
     */
    public function adminNoticeMinimumElementorVersion(): void
    {
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'inavii-social-feed-e'),
            '<strong>' . esc_html__('Inavii Social Feed', 'inavii-social-feed-e') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'inavii-social-feed-e') . '</strong>',
            INAVII_SOCIAL_FEED_E_MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required PHP version.
     */
    public function adminNoticeMinimumPhpVersion(): void
    {
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'inavii-social-feed-e'),
            '<strong>' . esc_html__('Inavii Social Feed', 'inavii-social-feed-e') . '</strong>',
            '<strong>' . esc_html__('PHP', 'inavii-social-feed-e') . '</strong>',
            INAVII_SOCIAL_FEED_E_MINIMUM_PHP_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function cronIssues()
    {
        $cronIssues = CronIssues::scheduleExists();

        if ($cronIssues) {
            return;
        }

        CronIssues::displayNotification();
    }

    public function instagramTokenIssues()
    {
        $tokenIssues = new TokenIssues();
        $issues = $tokenIssues->findExpiredTokenAccounts();

        if ($issues) {
            TokenIssues::displayNotification();
        }
    }
}
