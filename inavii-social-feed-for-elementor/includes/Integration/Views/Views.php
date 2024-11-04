<?php

namespace Inavii\Instagram\Includes\Integration\Views;

use Timber\Timber;

class Views
{
    public static function renderWithAjax($data)
    {
        return Timber::compile('view/index-dynamic.twig', $data);
    }

    public static function renderWithPhp($data)
    {
        return Timber::render('view/index.twig', $data);
    }

    public static function renderAjaxMessage(string $message)
    {
        return Timber::compile('view/no-posts.twig', ['message' => $message]);
    }

    public static function renderMessage(string $message)
    {
        return Timber::render('view/no-posts.twig', ['message' => $message]);
    }

    public static function rednerReconnectMessage($lastFeedUpdate)
    {
        return Timber::render('view/reconnect.twig', [
            'lastUpdate' => $lastFeedUpdate,
        ]);
    }
}