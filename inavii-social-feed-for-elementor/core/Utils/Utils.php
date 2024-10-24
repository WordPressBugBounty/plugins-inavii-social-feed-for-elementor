<?php

namespace Inavii\Instagram\Utils;

class Utils
{
    public static function pricingPageLink(): string
    {
        return add_query_arg(
            array(
                'page' => 'inavii-instagram-settings-pricing&trial=true',
            ),
            esc_url(admin_url('admin.php'))
        );
    }
}