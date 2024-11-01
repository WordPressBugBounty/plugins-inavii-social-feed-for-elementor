<?php

if (!defined('ABSPATH')) {
    exit;
}

use Inavii\Instagram\Admin\SettingsPage;
use Inavii\Instagram\Cron\Schedule;
use Inavii\Instagram\Includes\Dependence\AdminNotice;
use Inavii\Instagram\Includes\Dependence\RegisterAssets;
use Inavii\Instagram\Includes\Integration\WidgetsManager;
use Inavii\Instagram\PostTypes\Media\MediaPostType;
use Inavii\Instagram\Wp\ImportMediaBackgroundProcess;
use Inavii\Instagram\Wp\PostType;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\RestApi\RegisterRestApi;
use Inavii\Instagram\PostTypes\Account\AccountPostType;

add_action('init', static function () {
    PostType::register(new AccountPostType());
    PostType::register(new FeedPostType());
    PostType::register(new MediaPostType());
});

add_action('rest_api_init', static function () {
    RegisterRestApi::registerRoute();
});

SettingsPage::instance();

new RegisterAssets();
new AdminNotice();
new WidgetsManager();
new Schedule();
new ImportMediaBackgroundProcess();
