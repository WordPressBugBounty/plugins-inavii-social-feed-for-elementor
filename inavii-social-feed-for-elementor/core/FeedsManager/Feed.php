<?php

namespace Inavii\Instagram\FeedsManager;

class Feed {
    private $id;

    private $mediaType;

    private $mediaUrl;

    private $permalink;

    private $username;

    private $name;

    private $children;

    private $videoUrl;

    private $date;

    private $commentsCount;

    private $likeCount;

    private $caption;

    private $promotion;

    private $alternativeUrl;

    public function __construct(
        string $id,
        string $mediaType,
        array $mediaUrl,
        string $permalink,
        string $username,
        string $name,
        array $children,
        string $videoUrl,
        string $date,
        int $commentsCount,
        int $likeCount,
        string $caption,
        array $promotion,
        string $alternativeUrl
    ) {
        $this->id = $id;
        $this->mediaType = $mediaType;
        $this->mediaUrl = $mediaUrl;
        $this->permalink = $permalink;
        $this->username = $username;
        $this->name = $name;
        $this->children = $children;
        $this->videoUrl = $videoUrl;
        $this->date = $date;
        $this->commentsCount = $commentsCount;
        $this->likeCount = $likeCount;
        $this->caption = $caption;
        $this->promotion = $promotion;
        $this->alternativeUrl = $alternativeUrl;
    }

    public function id() : int {
        return $this->id;
    }

    public function mediaType() : string {
        return $this->mediaType;
    }

    public function mediaUrl() : array {
        return $this->mediaUrl;
    }

    public function alternativeUrl() : string {
        return $this->alternativeUrl;
    }

    public function permalink() : string {
        return $this->permalink;
    }

    public function username() : string {
        return $this->username;
    }

    public function name() : string {
        return $this->name;
    }

    public function children() : array {
        return $this->children;
    }

    public function videoUrl() : string {
        return $this->videoUrl;
    }

    public function date() : string {
        return $this->date;
    }

}
