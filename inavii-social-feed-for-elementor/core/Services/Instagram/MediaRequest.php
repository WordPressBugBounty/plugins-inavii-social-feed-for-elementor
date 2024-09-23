<?php

namespace Inavii\Instagram\Services\Instagram;

use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Services\Instagram\Post\BusinessPosts;
use Inavii\Instagram\Services\Instagram\Post\PrivatePosts;
use Inavii\Instagram\SystemStatus\AccountTokenIssues;
use Inavii\Instagram\Utils\TransformRemotenIstagramData;
use Throwable;

class MediaRequest
{
    private $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * Fetches Instagram posts based on account type and handles errors.
     *
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function request(): array
    {
        $instagramFetch = $this->resolvePostService();
        $accountIssues = new AccountTokenIssues($this->account->wpAccountID());

        try {
            $media = TransformRemotenIstagramData::transform($instagramFetch->posts($this->account->accessToken()));
            $accountIssues->clearAccountIssues();
            return $media;
        } catch (InstagramOAuthException|MessageNotProvidedException $e) {
            $accountIssues->setAccountIssues($e->getType());
            throw new $e($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Resolves the appropriate service for fetching posts based on the account type.
     *
     * @return BusinessPosts|PrivatePosts
     */
    private function resolvePostService()
    {
        if ($this->account->accountType() === AccountPostType::BUSINESS) {
            return new BusinessPosts($this->account->igAccountID());
        } else {
            return new PrivatePosts();
        }
    }
}
