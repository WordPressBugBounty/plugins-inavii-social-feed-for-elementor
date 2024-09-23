<?php

namespace Inavii\Instagram\RestApi\EndPoints\Media;

use Inavii\Instagram\Wp\ImportMediaBackgroundProcess;

class ImportMedia
{
    public function startImport(array $media = [])
    {
        if ($media) {
            $instagramImport = new ImportMediaBackgroundProcess();
            $instagramImport->scheduleMediaDownload($media);
            $instagramImport->registerEvents();
        }
    }
}