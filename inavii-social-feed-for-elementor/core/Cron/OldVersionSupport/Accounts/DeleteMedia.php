<?php

namespace Inavii\Instagram\Cron\OldVersionSupport\Accounts;

use Inavii\Instagram\Cron\OldVersionSupport\InstagramMediaComparer;
use Inavii\Instagram\Media\Media;
use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;

class DeleteMedia
{
    private $account;
    private $accountPostType;
    private $remoteMedia;
    private $mediaCompare;

    public function __construct(Account $account, $remoteMedia)
    {
        $this->account = $account;
        $this->accountPostType = new AccountPostType();
        $this->remoteMedia = $remoteMedia;
        $this->mediaCompare = new InstagramMediaComparer();
    }

    public function delete(): void
    {
        $actualMedia = $this->accountPostType->getMedia($this->account->wpAccountID());

        if (empty($actualMedia)) {
            return;
        }

        $newMedia = $this->remoteMedia;

        $mediaToBeRemoved = $this->mediaCompare->findElementsToDelete($actualMedia, $newMedia);

        foreach ($mediaToBeRemoved as $item) {
            Media::delete($item['id']);

            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    Media::delete($child['id']);
                }
            }
        }
    }
}