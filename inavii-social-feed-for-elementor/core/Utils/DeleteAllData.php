<?php

namespace Inavii\Instagram\Utils;

use Inavii\Instagram\Media\Media;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\PostTypes\Media\MediaPostType;

class DeleteAllData
{
    public static function delete()
    {
        $mediaPostType = new MediaPostType();
        $accountPostType = new AccountPostType();
        $feedPostType = new FeedPostType();

        $accountPostType->deleteAllPosts();
        $feedPostType->deleteAllPosts();
        $mediaPostType->deleteAllPosts();
        Media::deleteMediaDirectory();
    }
}