<?php

namespace Inavii\Instagram\PostTypes\Account;

use Inavii\Instagram\Media\Media;
use Inavii\Instagram\Wp\PostType;
use Inavii\Instagram\Wp\Query;
class AccountPostType extends PostType {
    public const BUSINESS = 'business';

    public const PERSONAL = 'personal';

    public const META_KEY_ACCOUNT = 'inavii_social_feed_account';

    public const META_KEY_MEDIA = 'inavii_social_feed_media';

    public const META_KEY_ACCOUNT_RELATED = 'inavii_social_feed_account_related';

    public const META_KEY_IMPORTER_MEDIA_STATUS = 'inavii_social_feed_importer_media_status';

    public function slug() : string {
        return 'inavii_account';
    }

    /**
     * @deprecated 2.4.3.
     */
    public function getAccountMedia( int $postID ) : array {
        return [
            'media' => $this->getMeta( $postID, self::META_KEY_MEDIA ),
        ];
    }

    public function get( int $postID ) : Account {
        return new Account(array_merge( (array) $this->getMeta( $postID, self::META_KEY_ACCOUNT ), [
            'wpAccountID' => $postID,
        ] ));
    }

    public function insert( string $title, array $data ) : int {
        return ( new Query($this->slug()) )->withPostTitle( $title )->withMetaInput( self::META_KEY_ACCOUNT, $data )->save();
    }

    public function posts( int $postNumber = -1 ) : array {
        return array_map( function ( $post ) {
            return array_merge( (array) $this->getMeta( $post->ID, self::META_KEY_ACCOUNT ), [
                'wpAccountID' => $post->ID,
            ] );
        }, ( new Query($this->slug()) )->numberOfPosts( $postNumber )->posts() );
    }

    /**
     * @deprecated 2.4.3.
     */
    public function getMedia( int $postID ) : array {
        $media = $this->getMeta( $postID, self::META_KEY_MEDIA );
        if ( !is_array( $media ) ) {
            return [];
        }
        return $media;
    }

    /**
     * @deprecated 2.4.3.
     */
    public function addMedia( int $postID, array $data ) : void {
        $this->updateMeta( $postID, self::META_KEY_MEDIA, $data );
    }

    public function deleteMedia( int $postID ) : void {
        $media = $this->getMedia( $postID );
        foreach ( $media as $item ) {
            if ( isset( $item['id'] ) ) {
                Media::delete( $item['id'] );
            }
        }
    }

    public function updateAccount( int $postID, array $data ) : void {
        $this->updateMeta( $postID, self::META_KEY_ACCOUNT, $data );
    }

    public function setImporterMediaStatus( int $accountID, bool $completed ) : void {
        $this->updateMeta( $accountID, self::META_KEY_IMPORTER_MEDIA_STATUS, $completed );
    }

    public function updateAvatar( int $accountID, $avatarUrl ) : void {
        $this->updateAccountProfile( $accountID, 'avatarOverwritten', $avatarUrl );
    }

    public function instagramFeedsLastUpdate( int $postID, string $methodLastUpdate = 'CRON' ) : void {
        $data = $this->getMeta( $postID, self::META_KEY_ACCOUNT );
        $data['lastUpdate'] = date( 'c' );
        $data['methodLastUpdate'] = $methodLastUpdate;
        $this->updateMeta( $postID, self::META_KEY_ACCOUNT, $data );
    }

    public function setAccountIssues( int $postID, string $error, bool $clear = false ) : void {
        $data = (array) $this->getMeta( $postID, self::META_KEY_ACCOUNT );
        if ( !isset( $data['issues'] ) ) {
            $data['issues'] = [
                'count'             => 0,
                'error'             => '',
                'reconnectRequired' => false,
            ];
        }
        $data['issues']['count'] = (int) $data['issues']['count'] + 1;
        $data['issues']['error'] = $error;
        if ( $data['issues']['count'] > 6 && $data['issues']['error'] === 'OAuthException' ) {
            $data['issues']['reconnectRequired'] = true;
        }
        if ( $clear ) {
            $data['issues']['count'] = 0;
            $data['issues']['error'] = '';
            $data['issues']['reconnectRequired'] = false;
        }
        $this->updateMeta( $postID, self::META_KEY_ACCOUNT, $data );
    }

    /**
     * @deprecated 2.4.3.
     */
    public function getAccountRelatedWithFeed( $postID ) : Account {
        return $this->get( (int) $this->getMeta( $postID, self::META_KEY_ACCOUNT_RELATED ) );
    }

    public function findBusinessAccount() : Account {
        $accounts = $this->getConnectedBusinessAccounts();
        if ( !$accounts ) {
            throw new \RuntimeException('No business account is connected, or reconnection of the account is required.');
        }
        return new Account(reset( $accounts ));
    }

    private function getConnectedBusinessAccounts() : array {
        return array_filter( $this->posts(), function ( $account ) {
            return $account['accountType'] === self::BUSINESS && $account['issues']['reconnectRequired'] === false;
        } );
    }

    private function updateAccountProfile( int $accountID, $metaKey, $value ) : void {
        $account = $this->getMeta( $accountID, self::META_KEY_ACCOUNT );
        $sanitizedValue = htmlspecialchars( $value ?? '', ENT_QUOTES, 'UTF-8' );
        if ( $account ) {
            $updatedAccountMeta = array_merge( $account, [
                $metaKey => $sanitizedValue,
            ] );
            $this->updateAccount( $accountID, $updatedAccountMeta );
        }
    }

    protected function args() : array {
        return array_merge( parent::args(), [
            'labels' => [
                'menu_name' => __( 'Inavii Account', 'inavii-social-feed-e' ),
            ],
        ] );
    }

}
