<?php

namespace Inavii\Instagram\Cron\OldVersionSupport\Accounts;

use Inavii\Instagram\Media\DownloadRemoteMedia;
use Inavii\Instagram\Media\GenerateThumbnails;
use Inavii\Instagram\Media\Media;
use Inavii\Instagram\PostTypes\Account\Account;
use Inavii\Instagram\PostTypes\Account\AccountPostType;

class AddNewMedia
{
    private $account;
    private $accountPostType;
    private $downloadRemoteMedia;
    private $thumbnails;
    private $remoteMedia;

    public function __construct(Account $account, $remoteMedia)
    {
        $this->account = $account;
        $this->accountPostType = new AccountPostType();
        $this->downloadRemoteMedia = new DownloadRemoteMedia();
        $this->thumbnails = new GenerateThumbnails();
        $this->remoteMedia = $remoteMedia;
    }

    public function addMedia(): void
    {
        $newMedia = $this->remoteMedia;

        $mediaFromAccount = $this->accountPostType->getMedia($this->account->wpAccountID());

        if (empty($mediaFromAccount)) {
            return;
        }

        $this->accountPostType->addMedia($this->account->wpAccountID(), $newMedia);

        $this->generateMedia($newMedia);
    }

    private function generateMedia($posts): void
    {
        foreach ($posts as $post) {
            if (!$this->mediaExists($post['id'])) {
                $this->downloadRemoteMedia->save($post['url'], $post['id']);
                $this->thumbnails->generate($post['id']);

                Media::deleteImage($post['id'], 'full');
            }

            if (!empty($post['children'])) {
                foreach ($post['children'] as $child) {
                    if (!$this->mediaExists($child['id'])) {
                        $this->downloadRemoteMedia->save($child['url'], $child['id']);
                        $this->thumbnails->generate($child['id']);

                        Media::deleteImage($child['id'], 'full');
                    }
                }
            }
        }
    }

    private function mediaExists($postId): bool
    {
        return Media::imageExist($postId . '-l') && Media::imageExist($postId . '-m');
    }
}
