<?php

namespace Inavii\Instagram\Cron\OldVersionSupport\Feeds;

use Inavii\Instagram\Cron\OldVersionSupport\InstagramMediaUpdate;
use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Utils\VersionChecker;
class UpdateFeedAddNewMedia {
    private $account;

    private $accountPostType;

    private $feedPostType;

    public function __construct( Account $account ) {
        $this->account = $account;
        $this->accountPostType = new AccountPostType();
        $this->feedPostType = new FeedPostType();
    }

    public function update() : void {
        $this->basicUpdate();
    }

    private function basicUpdate() : void {
        $feedsIds = $this->feedPostType->getRelatedFeedsIds( $this->account->wpAccountID() );
        $accountMedia = $this->accountPostType->getMedia( $this->account->wpAccountID() );
        if ( empty( $accountMedia ) ) {
            return;
        }
        foreach ( $feedsIds as $id ) {
            $this->feedPostType->updateMedia( $id, $accountMedia );
        }
    }

}
