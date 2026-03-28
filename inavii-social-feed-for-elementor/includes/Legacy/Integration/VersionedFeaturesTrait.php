<?php

namespace Inavii\Instagram\Includes\Legacy\Integration;

use Inavii\Instagram\Freemius\FreemiusAccess;
use Inavii\Instagram\Settings\PricingPage;

trait VersionedFeaturesTrait
{
    public static function customChoicesClass(): string
    {
        return 'inavii-pro__custom-choices';
    }

    public static function premiumInfo(): string
    {
        if (FreemiusAccess::canUsePremiumCode()) {
            return '';
        }

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url(PricingPage::url()),
            __('Start 3-day trial', 'inavii-social-feed-e')
        );
    }

    private static function isFree(): bool
    {
        return !FreemiusAccess::canUsePremiumCode();
    }

    private static function titleLabelProClass(): string
    {
        return self::isFree() ? 'inavii-pro__title-label-pro' : '';
    }

    private static function optionProClass(): string
    {
        return self::isFree() ? 'inavii-pro__option-pro' : '';
    }

    public static function titleIconClass(): string
    {
        return 'inavii-pro__title-icon';
    }

    private static function customChoicesTwoRowClass(): string
    {
        return self::isFree() ? 'inavii-pro__custom-choices--2-row inavii-pro__control-open-in' : 'inavii-pro__custom-choices--2-row';
    }

    private static function customChoicesLabelProClass(): string
    {
        return self::isFree() ? 'inavii-pro__custom-choices-label-pro' : '';
    }

    private static function buttonClassGetPro(): string
    {
        return self::isFree() ? 'inavii-pro__get-pro' : 'elementor-hidden';
    }

    private static function defaultValueFreeEmpty(): string
    {
        return self::isFree() ? 'no' : 'null';
    }

    private static function defaultValueForVersion(): string
    {
        return self::isFree() ? 'no' : 'yes';
    }

    private static function imageClickActions(): string
    {
        return self::isFree() ? 'popup' : 'lightbox';
    }

    private static function renderTypePro(): string
    {
        return self::isFree() ? 'none' : 'template';
    }

    private static function settingsPageLink(): string
    {
        return add_query_arg(
            array(
                'page' => 'inavii-instagram-settings',
            ),
            esc_url(admin_url('admin.php'))
        );
    }
}
