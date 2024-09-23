<?php

namespace Inavii\Instagram\Utils;

class FiltersFeed {
    private $filters;

    private $feed;

    public function __construct( array $feed, array $filters ) {
        $this->feed = $feed;
        $this->filters = $filters;
    }

    public function filter() : array {
        $this->feed = $this->filterPosts();
        return $this->feed;
    }

    private function filterPosts() : array {
        if ( !isset( $this->filters['postOrder'] ) ) {
            return $this->feed;
        }
        switch ( $this->filters['postOrder'] ) {
            case 'likeCount':
                usort( $this->feed, function ( $a, $b ) {
                    return $b['likeCount'] - $a['likeCount'];
                } );
                break;
            case 'commentCount':
                usort( $this->feed, function ( $a, $b ) {
                    return $b['commentsCount'] - $a['commentsCount'];
                } );
                break;
            case 'mostRecentFirst':
                usort( $this->feed, function ( $a, $b ) {
                    return strtotime( $b['date'] ) - strtotime( $a['date'] );
                } );
                break;
            case 'oldestFirst':
                usort( $this->feed, function ( $a, $b ) {
                    return strtotime( $a['date'] ) - strtotime( $b['date'] );
                } );
                break;
            case 'random':
                shuffle( $this->feed );
                break;
        }
        return $this->feed;
    }

}
