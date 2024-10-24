<?php

namespace Inavii\Instagram\Cron;

use Inavii\Instagram\Cron\OldVersionSupport\Accounts\AddNewMedia;
use Inavii\Instagram\Cron\OldVersionSupport\Accounts\DeleteMedia;
use Inavii\Instagram\Cron\OldVersionSupport\Feeds\UpdateFeedAddNewMedia;
use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MediaRequest;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\SystemStatus\EmailNotification;
use Inavii\Instagram\SystemStatus\TokenIssues;
use Inavii\Instagram\Utils\VersionChecker;
class Schedule {
    private $accountPostType;

    public function __construct() {
        add_action( 'inavii_social_feed_update_media', [$this, 'update'] );
        add_action( 'inavii_social_feed_refresh_token', [$this, 'refreshAccessToken'] );
        $this->accountPostType = new AccountPostType();
    }

    public function update() : void {
        $this->updateMediaOldVersion();
        ( new UpdateMedia() )->update();
    }

    /**
     * @deprecated 2.4.3.
     */
    public function updateMediaOldVersion() : void {
        foreach ( $this->accountPostType->posts() as $account ) {
            $accountObj = new Account($account);
            $media = $this->accountPostType->getMedia( $accountObj->wpAccountID() );
            if ( empty( $media ) ) {
                continue;
            }
            try {
                $remoteMedia = ( new MediaRequest($accountObj) )->request();
                ( new DeleteMedia($accountObj, $remoteMedia) )->delete();
                ( new AddNewMedia($accountObj, $remoteMedia) )->addMedia();
                ( new UpdateFeedAddNewMedia($accountObj) )->update();
            } catch ( InstagramOAuthException|MessageNotProvidedException $e ) {
            }
        }
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     * /
     * @deprecated 2.4.3.
     */
    public function updateSingleAccount( $accountID ) {
        $account = $this->accountPostType->get( $accountID );
        try {
            $remoteMedia = ( new MediaRequest($account) )->request();
            ( new DeleteMedia($account, $remoteMedia) )->delete();
            ( new AddNewMedia($account, $remoteMedia) )->addMedia();
            ( new UpdateFeedAddNewMedia($account) )->update();
        } catch ( InstagramOAuthException|MessageNotProvidedException $e ) {
            throw new $e($e->getMessage(), $e->getCode());
        }
        return $account->wpAccountID();
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function updateManually( $source ) {
        ( new UpdateMedia() )->updateManually( $source );
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function refreshAccessToken() : void {
        foreach ( $this->accountPostType->posts() as $account ) {
            ( new RefreshAccessToken(new Account($account)) )->refresh();
        }
    }

}
