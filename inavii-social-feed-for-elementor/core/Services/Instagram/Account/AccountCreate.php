<?php

namespace Inavii\Instagram\Services\Instagram\Account;

use Inavii\Instagram\Media\DownloadRemoteMedia;
use Inavii\Instagram\Media\GenerateThumbnails;
use Inavii\Instagram\Media\Media;
use Inavii\Instagram\PostTypes\Account\AccountPostType;

class AccountCreate
{
    private $account;
    private $instagramUser;

    public function __construct(InstagramAccount $instagramUser)
    {
        $this->account = new AccountPostType();
        $this->instagramUser = $instagramUser;
    }

    public function create(): int
    {
        return $this->account->insert($this->instagramUser->userName(), [
            'id' => $this->instagramUser->id(),
            'name' => $this->instagramUser->name(),
            'username' => $this->instagramUser->userName(),
            'accountType' => $this->instagramUser->accountType(),
            'accessToken' => $this->instagramUser->accessToken(),
            'mediaCount' => $this->instagramUser->mediaCount(),
            'avatar' => $this->generateUserAvatar($this->instagramUser->avatar(), $this->instagramUser->id()),
            'tokenExpires' => $this->instagramUser->tokenExpires(),
            'biography' => wp_encode_emoji($this->instagramUser->biography() ?? ''),
        ]);
    }

    private function generateUserAvatar($avatar, $id): string
    {
        if ($avatar) {
            return $this->generateThumbnailUrl($avatar, $id);
        }

        return Media::assetImageUrl('placehorder-user.svg');
    }

    private function generateThumbnailUrl(string $url, string $id): string
    {
        $downloadRemoteMedia = new DownloadRemoteMedia();
        $thumbnails = new GenerateThumbnails();

        $downloadRemoteMedia->save($url, $id);
        $thumbnails->generate($id, Media::IMAGE_SMALL);

        Media::deleteImage($id, 'full');

        return Media::mediaUrl($id)['small'];
    }
}