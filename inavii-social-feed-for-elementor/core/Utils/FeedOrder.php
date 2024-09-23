<?php

namespace Inavii\Instagram\Utils;

use Inavii\Instagram\PostTypes\Media\MediaPostType;

class FeedOrder
{
    public $key;
    public $valueType;
    public $order;
    public $isRandom = false;

    public function __construct(string $key, string $valueType, string $order, bool $isRandom = false)
    {
        $this->key = $key;
        $this->valueType = $valueType;
        $this->order = $order;
        $this->isRandom = $isRandom;
    }

    public static function create(string $order): FeedOrder
    {
        switch ($order) {
            case 'likeCount':
                return new self(MediaPostType::LIKES_COUNT, 'NUMERIC', 'DESC');
            case 'commentCount':
                return new self(MediaPostType::COMMENTS_COUNT, 'NUMERIC', 'DESC');
            case 'mostRecentFirst':
                return new self(MediaPostType::DATE, 'CHAR', 'DESC');
            case 'oldestFirst':
                return new self(MediaPostType::DATE, 'CHAR', 'ASC');
            case 'lastRequested':
                return new self(MediaPostType::LAST_REQUESTED, 'CHAR', 'DESC');
            case 'random':
                return new self('', '', '', true);
            default :
                return self::create('', '', '');
        }
    }
}