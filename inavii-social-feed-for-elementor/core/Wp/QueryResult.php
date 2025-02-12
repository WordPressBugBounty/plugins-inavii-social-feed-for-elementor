<?php

namespace Inavii\Instagram\Wp;

class QueryResult
{
    private array $posts;
    private int $total;

    public function __construct(array $posts, int $total)
    {
        $this->posts = $posts;
        $this->total = $total;
    }

    public function getPosts(): array
    {
        return $this->posts;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function toArray(): array
    {
        return [
            'posts' => $this->posts,
            'total' => $this->total,
        ];
    }
}
