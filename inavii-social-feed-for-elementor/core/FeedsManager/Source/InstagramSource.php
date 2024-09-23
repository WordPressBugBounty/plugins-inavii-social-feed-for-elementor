<?php

namespace Inavii\Instagram\FeedsManager\Source;

use Inavii\Instagram\PostTypes\Account\AccountPostType;

class InstagramSource
{

    public static function create(int $accountId): string
    {
        $accountsPostType = new AccountPostType();
        $account = $accountsPostType->get($accountId);

        return $account->userName() . "|" . strtoupper($account->accountType());
    }

    /**
     * @throws FeedSourceException
     */
    public static function get(array $accountIds): array
    {
        $accountsPostType = new AccountPostType();
        $sourceResult = [];

        foreach ($accountIds as $accountId) {
            $account = $accountsPostType->get($accountId);
            $sourceResult[] = $account->userName() . "|" . strtoupper($account->accountType());
        }

        if (!$sourceResult) {
            throw new FeedSourceException('Source is not valid');
        }

        return $sourceResult;
    }
}