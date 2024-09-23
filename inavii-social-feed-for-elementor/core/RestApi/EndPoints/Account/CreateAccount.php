<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Services\Instagram\Account\AccountCreate;
use Inavii\Instagram\Services\Instagram\Account\InstagramAccount;

class CreateAccount
{
    private $account;

    public function __construct(string $accountType)
    {
        $this->account = new AccountPostType();
    }

    public function create(InstagramAccount $accountData): array
    {
        $newAccountId = (new AccountCreate($accountData))->create();

        $this->account->instagramFeedsLastUpdate($newAccountId);
        $this->account->setAccountIssues($newAccountId, '', true);

        return [
            'wpAccountID' => $newAccountId,
            'name' => $accountData->userName() ?? $accountData->name(),
        ];
    }
}