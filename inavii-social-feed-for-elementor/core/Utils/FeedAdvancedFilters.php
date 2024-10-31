<?php

namespace Inavii\Instagram\Utils;

class FeedAdvancedFilters {
    public static function customOrderPostIds( $settings ) {
        return [];
    }

    public static function customOrderPostIdsForApi( $settings ) : array {
        return [];
    }

    public static function moderateWhiteList( array $settings ) : array {
        return self::getModerationList( $settings, 'whitelist' );
    }

    public static function moderateBlackList( array $settings ) : array {
        return self::getModerationList( $settings, 'blacklist' );
    }

    private static function moderatePostsEnable( array $settings ) : bool {
        return false;
        return $settings['moderateHidePost'] ?? true;
    }

    private static function getModerationList( array $settings, string $mode ) : array {
        $moderationMode = $settings['moderationMode'] ?? 'blacklist';
        if ( isset( $settings['dragAndDrop'] ) && $settings['dragAndDrop'] === true ) {
            return [];
        }
        if ( self::moderatePostsEnable( $settings ) && $moderationMode === $mode ) {
            return $settings['moderation'] ?? [];
        }
        return [];
    }

}
