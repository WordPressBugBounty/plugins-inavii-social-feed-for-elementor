<?php

namespace Inavii\Instagram\Cron;

use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Services\Instagram\Token\AccessToken;

class RefreshAccessToken
{
    private $account;

    private $accessToken;

    private $accountPostType;

    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->accessToken = new AccessToken($account->accessToken());
        $this->accountPostType = new AccountPostType();
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function refresh(): void
    {
        if ($this->account->accountType() === AccountPostType::PERSONAL) {
            $newToken = $this->accessToken->refresh();
            $accountData = $this->account->meta();
            $accountData['accessToken'] = $newToken->newAccessToken();
            $accountData['tokenExpires'] = $newToken->tokenExpires();
            $accountData['lastUpdate'] = date('Y-m-d H:i:s');

            $this->accountPostType->updateAccount($this->account->wpAccountID(), $accountData);
        }
    }
}