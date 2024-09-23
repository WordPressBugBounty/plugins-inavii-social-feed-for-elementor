<?php

namespace Inavii\Instagram\SystemStatus;

use Inavii\Instagram\PostTypes\Account\AccountPostType;

class AccountTokenIssues
{
    private $accountPostType;
    private $accountID;

    public function __construct($accountID)
    {
        $this->accountPostType = new AccountPostType();
        $this->accountID = $accountID;
    }

    public function setAccountIssues($message): void
    {
        $this->accountPostType->setAccountIssues($this->accountID, $message);
    }

    public function clearAccountIssues(): void
    {
        $this->accountPostType->setAccountIssues($this->accountID, '', true);
        $this->accountPostType->instagramFeedsLastUpdate($this->accountID);
    }
}