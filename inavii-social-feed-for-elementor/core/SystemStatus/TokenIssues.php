<?php

namespace Inavii\Instagram\SystemStatus;

use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Utils\TimeChecker;

class TokenIssues
{
    private $accountsPostType;

    public function __construct()
    {
        $this->accountsPostType = new AccountPostType();
    }

    public function findExpiredTokenAccounts(): bool
    {
        $accounts = $this->accountsPostType->posts();
        $expiredTokenFound = false;

        foreach ($accounts as $accountData) {
            $account = new Account($accountData);
            $issues = $account->issues();
            $lastUpdate = TimeChecker::calculateTimeDifference($account->lastUpdate());

            if ($issues['reconnectRequired'] || $lastUpdate->days > 5) {
                $expiredTokenFound = true;
                break;
            }
        }

        return $expiredTokenFound;
    }

    public static function displayNotification(): void
    {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Inavii Social Feed</strong></p>
            <p>Reconnect your Instagram account because your access token has expired. Reconnecting is necessary because
                the Instagram API token periodically expires </p>

            <p> Please go to the
                <a href="/wp-admin/admin.php?page=inavii-instagram-settings#/accounts">
                    <strong>Inavii Social feed » IG Accounts</strong>
                </a>
                page and <strong>Reconnect Account</strong>
            </p>

        </div>

        <?php
    }
}