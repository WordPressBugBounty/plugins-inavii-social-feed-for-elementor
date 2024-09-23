<?php

namespace Inavii\Instagram\Utils;

use Inavii\Instagram\Media\Media;

class TransformRemotenIstagramData
{
    public static function transform(array $data): array
    {
        return array_map(static function ($post) {
            return [
                'id' => $post['id'],
                'mediaId' => $post['id'],
                'url' => isset($post['children']) ? self::getCarouselAlbum($post['children'])[0]['url'] : self::mediaType($post),
                'mediaUrl' => Media::mediaUrl($post['id']),
                'mediaType' => $post['media_type'],
                'videoUrl' => self::getVideoUrl($post),
                'permalink' => $post['permalink'],
                'username' => $post['username'] ?? '',
                'date' => $post['timestamp'] ?? '',
                'commentsCount' => $post['comments_count'] ?? 0,
                'likeCount' => $post['like_count'] ?? 0,
                'caption' => $post['caption'] ?? '',
                'mediaProductType' => $post['media_product_type'] ?? '',
                'show' => true,
                'children' => isset($post['children']) ? self::getCarouselAlbum($post['children']) : [],
            ];
        }, $data);
    }

    public static function addSource($data, $key, $value): array
    {
        return array_map(function ($element) use ($key, $value) {
            $element[$key] = $value;
            return $element;
        }, $data);
    }

    private static function getCarouselAlbum($post): array
    {
        return array_map(static function ($post) {
            return [
                'id' => $post['id'],
                'mediaType' => $post['media_type'],
                'mediaUrl' => Media::mediaUrl($post['id']),
                'videoUrl' => self::getVideoUrl($post),
                'url' => self::mediaType($post),
            ];
        }, $post['data'] ?? []);
    }

    private static function mediaType($medium): string
    {
        return $medium['media_type'] === 'VIDEO'
            ? ($medium['thumbnail_url'] ?? '')
            : ($medium['media_url'] ?? '');
    }

    private static function getVideoUrl($post)
    {
        if ($post['media_type'] === 'VIDEO') {
            return $post['media_url'] ?? '';
        }

        return null;
    }
}
