<?php

namespace Inavii\Instagram\RestApi\EndPoints\Media;

use Inavii\Instagram\Media\GenerateThumbnails;
use Inavii\Instagram\Media\Media;

class GenerateThumbnailsProcessor
{
    private $thumbnails;

    public function __construct()
    {
        $this->thumbnails = new GenerateThumbnails();
    }

    public function task($items): bool
    {
        foreach ($items as $item) {
            $this->thumbnails->generate($item['id'], Media::IMAGE_LARGE);
            Media::deleteImage($item['id'], 'full');

            if (!empty($item['children'])) {
                $this->generateCarouselAlbumThumbnails($item['children']);
            }
        }

        return true;
    }

    private function generateCarouselAlbumThumbnails($children): void
    {
        foreach ($children as $child) {
            $this->thumbnails->generate($child['id'], Media::IMAGE_LARGE);

            Media::deleteImage($child['id'], 'full');
        }
    }
}