<?php

namespace Inavii\Instagram\FeedsManager;

use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;

class GetAccountsBySource
{
    private $settings;
    private $account;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->account = new AccountPostType();
    }

    public function get(): Account
    {
        $source = $this->settings['source'] ?? [];

       if (!$source) {
           return new Account([]);
        }

        foreach ($source as $key => $value) {

            if ($key === 'accounts' || $key === 'tagged') {

                if (empty($value)) {
                    continue;
                }
                return $this->account->get($value[0]);
            }
        }
        return new Account([]);
    }
}