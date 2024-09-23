<?php

namespace Inavii\Instagram\Utils;

class VersionChecker
{
    public static function version(): \Freemius
    {
        return inavii_social_feed_e_fs();
    }
}