<?php

namespace Inavii\Instagram\Includes\Integration;

use Inavii\Instagram\Utils\Utils;
use Inavii\Instagram\Utils\VersionChecker;

trait VersionedFeaturesTrait
{
    public static function customChoicesClass(): string
    {
        return 'inavii-pro__custom-choices';
    }

    public static function premiumInfo(): string
    {
        if (VersionChecker::version()->is_premium() && VersionChecker::version()->can_use_premium_code()) {
            return '';
        }

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url(Utils::pricingPageLink()),
            __('Start 3-day trial', 'inavii-social-feed-e')
        );
    }

    private static function isFree(): bool
    {
        if (!VersionChecker::version()->is_premium()) {
            return true;
        }

        if (!VersionChecker::version()->can_use_premium_code()) {
            return true;
        }

        return false;
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