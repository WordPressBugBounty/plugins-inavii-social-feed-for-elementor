<?php

namespace Inavii\Instagram\RestApi\EndPoints\Media;

use Inavii\Instagram\Media\DownloadRemoteMedia;
use Inavii\Instagram\Media\GenerateThumbnails;
use Inavii\Instagram\Media\Media;

class ImporterMediaProcessor
{
    private $downloadRemoteMedia;
    private $thumbnails;

    public function __construct()
    {
        $this->downloadRemoteMedia = new DownloadRemoteMedia();
        $this->thumbnails = new GenerateThumbnails();
    }

    public function task($items): bool
    {
        foreach ($items as $item) {
			if (!Media::mediaExist($item['id'], '-m')) {
				$this->downloadRemoteMedia->save($item['url'] ?? '', $item['id']);
				$this->thumbnails->generate($item['id'], Media::IMAGE_MEDIUM);
			}

            if (!empty($item['children'])) {
                $this->importCarouselAlbum($item['children']);
            }
        }
        return true;
    }

    private function importCarouselAlbum($children): void
    {
        foreach ($children as $child) {
			if (!Media::mediaExist($child['id'], '-l')) {
				$this->downloadRemoteMedia->save($child['url'] ?? '', $child['id']);
			}
        }
    }
}