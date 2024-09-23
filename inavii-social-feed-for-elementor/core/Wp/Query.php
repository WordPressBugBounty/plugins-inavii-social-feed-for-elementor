<?php

namespace Inavii\Instagram\Wp;

class Query
{
    private $query = [];

    public function __construct(string $postTypeSlug)
    {
        $this->query['post_type'] = $postTypeSlug;
        $this->query['post_status'] = 'publish';
    }

    public function withPostTitle(string $postTitle): self
    {
        $this->query['post_title'] = $postTitle;

        return $this;
    }

    public function withMetaInput(string $key, $value): self
    {
        $this->query['meta_input'][$key] = $value;

        return $this;
    }

    public function withMetaQuery(string $key, $value, string $compare = '='): self
    {
        if (is_array($value)) {
            return $this->withMetaQueryRelation($key, $value);
        }

        $this->query['meta_query'][] = [
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
        ];

        return $this;
    }

    public function withMetaQueryRelation(string $key, array $values, string $compare = '=', string $relation = 'OR'): self
    {
       if (empty($values)) {
            return $this;
        }

        $metaQuery = ['relation' => $relation];

        foreach ($values as $value) {
            $metaQuery[] = [
                'key' => $key,
                'value' => $value,
                'compare' => $compare,
            ];
        }

        $this->query['meta_query'][] = $metaQuery;

        return $this;
    }

    public function withExcludePosts(array $postsID): self
    {
        if (empty($postsID)) {
            return $this;
        }

        $this->query['post__not_in'] = $postsID;

        return $this;
    }

    public function withSpecificPosts(array $postsID): self
    {
        if (empty($postsID)) {
            return $this;
        }

        $this->query['post__in'] = $postsID;
        $this->query['orderby'] = 'post__in';

        return $this;
    }

    public function withOffset(int $offset = 0): self
    {
        if ($offset < 0 || $offset === 0 ) {
            return $this;
        }

        $this->query['offset'] = $offset;

        return $this;
    }

    public function orderByMetaValue(string $metaKey, string $valueType = 'CHAR', string $order = 'ASC', bool $random = false): self
    {
        if ($random) {
            $this->query['orderby'] = 'rand';
            return $this;
        }

        $this->query['meta_key'] = $metaKey;
        $this->query['orderby'] = $valueType === 'NUMERIC' ? 'meta_value_num' : 'meta_value';
        $this->query['order'] = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        return $this;
    }

    public function numberOfPosts(int $number = -1): self
    {
        $this->query['posts_per_page'] = $number;

        return $this;
    }

    public function order($order): self
    {
        $this->query['order'] = $order;

        return $this;
    }

    public function findPostByTitle(): \WP_Query
    {
        $args = [
            'post_type' => $this->query['post_type'],
            'post_status' => 'publish',
            'title' => $this->query['post_title'],
            'posts_per_page' => 1,
        ];

        return new \WP_Query($args);
    }

    public function save()
    {
        $postExists = $this->findPostByTitle();

        if ($postExists->have_posts()) {
            $data = array_merge($this->query, ['ID' => $postExists->posts[0]->ID]);

            return wp_update_post($data);
        }

        $id = wp_insert_post($this->query);

        $this->query = [];

        return $id;
    }

    public function saveMedia(string $immutableInput = '')
    {
        $postExists = $this->findPostByTitle();

        if ($postExists->have_posts()) {
            $id = $postExists->posts[0]->ID;

            $existing_meta = get_post_meta($id, $immutableInput);

            if (!in_array($this->query['meta_input'][$immutableInput], $existing_meta)) {
                add_post_meta($id, $immutableInput, $this->query['meta_input'][$immutableInput]);
                unset($this->query['meta_input'][$immutableInput]);
                return wp_update_post(array_merge($this->query, ['ID' => $id]));
            }

            unset($this->query['meta_input'][$immutableInput]);

            return wp_update_post(array_merge($this->query, ['ID' => $id]));
        }

        $id = wp_insert_post($this->query);

        $this->query = [];

        return $id;
    }

    public function withFields($param): self
    {
        $this->query['fields'] = $param;

        return $this;
    }

    public function posts(): array
    {
        return get_posts($this->query);
    }

    public function post($postID)
    {
        return get_post($postID);
    }

    public function countPosts(): int
    {
        try {
            $args = $this->query;
            $args['fields'] = 'ids';
            $args['posts_per_page'] = -1;

            $posts = get_posts($args);

            return is_array($posts) ? count($posts) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
