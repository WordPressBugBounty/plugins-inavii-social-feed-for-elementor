<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\Utils;

use Inavii\Instagram\Freemius\FreemiusAccess;

class FeedAdvancedFilters {

	public static function customOrderPostIds( $settings ) {
		if ( FreemiusAccess::canUsePlanOrTrial( 'premium' ) ) {

			if ( isset( $settings['dragAndDrop'] ) && $settings['dragAndDrop'] === false ) {
				return [];
			}

			$excludePosts   = $settings['moderation'] ?? [];
			$customOrder    = $settings['dragAndDropData'] ?? [];
			$moderationMode = $settings['moderationMode'] ?? 'blacklist';

			if ( isset( $settings['moderateHidePost'] ) && $settings['moderateHidePost'] === false ) {
				return self::customOrderPostIdsForApi( $settings );
			}

			return array_map(
				function ( $item ) {
					return $item['id'];
				},
				array_filter(
					$customOrder,
					function ( $item ) use ( $excludePosts, $moderationMode ) {
						if ( $moderationMode === 'whitelist' ) {
							return in_array( $item['id'], $excludePosts );
						}
						return ! in_array( $item['id'], $excludePosts );
					}
				)
			);
		}

		return [];
	}

	public static function customOrderPostIdsForApi( $settings ): array {
		if ( FreemiusAccess::canUsePlanOrTrial( 'premium' ) ) {
			if ( $settings['dragAndDrop'] === false ) {
				return [];
			}

			return array_map(
				function ( $item ) {
					return $item['id'] ?? 0;
				},
				$settings['dragAndDropData'] ?? []
			);
		}

		return [];
	}

	public static function moderateWhiteList( array $settings ): array {
		return self::getModerationList( $settings, 'whitelist' );
	}

	public static function moderateBlackList( array $settings ): array {
		return self::getModerationList( $settings, 'blacklist' );
	}

	private static function moderatePostsEnable( array $settings ): bool {
		if ( ! FreemiusAccess::canUsePlanOrTrial( 'premium' ) ) {
			return false;
		}

		return $settings['moderateHidePost'] ?? true;
	}

	private static function getModerationList( array $settings, string $mode ): array {
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
