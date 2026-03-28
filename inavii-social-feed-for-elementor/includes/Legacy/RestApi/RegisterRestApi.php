<?php

declare (strict_types = 1);
namespace Inavii\Instagram\Includes\Legacy\RestApi;

use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Account\AccessTokenController;
use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Account\AccountController;
use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Feeds\FeedController;
use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Media\MediaSourceController;
use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\App\Settings;
use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Front\FrontFeed;
use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Front\LoadMore;
use Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Templates\Template;
use Inavii\Instagram\Freemius\FreemiusAccess;
use WP_REST_Server;
use function Inavii\Instagram\Di\container;
class RegisterRestApi {
    private static string $nameSpace = 'inavii/v1';

    private static function config() : array {
        return [
            /**
             * Settings
             */
            [
                'route'    => 'app/settings',
                'methods'  => 'GET',
                'callback' => [container()->get( Settings::class ), 'settings'],
            ],
            [
                'route'    => 'app/global-settings',
                'methods'  => 'PUT',
                'callback' => [container()->get( Settings::class ), 'updateGlobalSettings'],
            ],
            [
                'route'    => 'app/settings/delete/allData',
                'methods'  => 'POST',
                'callback' => [container()->get( Settings::class ), 'deleteAllPlatformData'],
            ],
            [
                'route'    => 'app/ui-version/v3',
                'methods'  => 'POST',
                'callback' => [container()->get( Settings::class ), 'switchToV3'],
            ],
            /**
             * Accounts
             */
            [
                'route'    => 'account',
                'methods'  => 'GET',
                'callback' => [container()->get( AccountController::class ), 'all'],
            ],
            [
                'route'    => 'account/update',
                'methods'  => 'POST',
                'callback' => [container()->get( AccountController::class ), 'update'],
            ],
            [
                'route'    => 'account/bio',
                'methods'  => 'POST',
                'callback' => [container()->get( AccountController::class ), 'updateBio'],
            ],
            [
                'route'    => 'account/delete/(?P<id>\\d+)',
                'methods'  => 'DELETE',
                'callback' => [container()->get( AccountController::class ), 'delete'],
            ],
            [
                'route'    => 'account/reconnect',
                'methods'  => 'POST',
                'callback' => [container()->get( AccountController::class ), 'reconnect'],
            ],
            /**
             * ╭──────────────────────────╮
             * | API: Instagram Account   |
             * ╰──────────────────────────╯
             */
            [
                'route'    => 'instagram/personal/account/create',
                'methods'  => 'POST',
                'callback' => [container()->get( AccountController::class ), 'connectAccount'],
            ],
            [
                'route'    => 'instagram/business/account/create',
                'methods'  => 'POST',
                'callback' => [container()->get( AccountController::class ), 'connectAccount'],
            ],
            /**
             * ╭───────────────────────────────╮
             * | API: Access Token Generator   |
             * ╰───────────────────────────────╯
             */
            [
                'route'    => 'instagram/accessTokenGenerator',
                'methods'  => 'POST',
                'callback' => [container()->get( AccessTokenController::class ), 'connect'],
            ],
            [
                'route'    => 'account/cron',
                'methods'  => 'PUT',
                'callback' => [container()->get( AccountController::class ), 'cron'],
            ],
            /**
             * ╭────────────╮
             * | API: Feeds |
             * ╰────────────╯
             */
            [
                'route'    => 'feed/(?P<id>\\d+)',
                'methods'  => 'GET',
                'callback' => [container()->get( FeedController::class ), 'get'],
                'args'     => [
                    'numberOfPosts' => [
                        'required'          => false,
                        'validate_callback' => function ( $param ) {
                            return is_numeric( $param );
                        },
                    ],
                ],
            ],
            [
                'route'    => 'feeds',
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [container()->get( FeedController::class ), 'all'],
            ],
            [
                'route'    => 'feeds/create',
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [container()->get( FeedController::class ), 'create'],
            ],
            [
                'route'    => 'feeds/delete/(?P<id>\\d+)',
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [container()->get( FeedController::class ), 'delete'],
            ],
            [
                'route'    => 'feed/update',
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [container()->get( FeedController::class ), 'update'],
            ],
            [
                'route'    => 'media/create',
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [container()->get( MediaSourceController::class ), 'create'],
            ],
            [
                'route'               => 'front/feed',
                'methods'             => WP_REST_Server::EDITABLE,
                'permission_callback' => [RestApiPublicAuth::class, 'isAuthorized'],
                'callback'            => [new FrontFeed(), 'get'],
            ],
        ];
    }

    private static function frontConfig() : array {
        return [[
            'route'               => 'front/feed',
            'methods'             => WP_REST_Server::EDITABLE,
            'permission_callback' => [RestApiPublicAuth::class, 'isAuthorized'],
            'callback'            => [new FrontFeed(), 'get'],
        ], [
            'route'               => 'front/feed/loadMore',
            'methods'             => 'POST',
            'permission_callback' => [RestApiPublicAuth::class, 'isAuthorized'],
            'callback'            => [new LoadMore(), 'get'],
        ]];
    }

    private static function registerMany( array $routes ) : void {
        foreach ( $routes as $config ) {
            $permissionCallback = $config['permission_callback'] ?? function () {
                return current_user_can( 'manage_options' );
            };
            register_rest_route( self::$nameSpace, $config['route'], [
                'methods'             => $config['methods'],
                'callback'            => $config['callback'],
                'permission_callback' => $permissionCallback,
            ] );
        }
    }

    public static function registerFrontRoutes() : void {
        self::registerMany( self::frontConfig() );
    }

    public static function registerRoute() : void {
        $mergedConfig = self::config();
        if ( FreemiusAccess::canUsePremiumCode() ) {
            $mergedConfig = array_merge( $mergedConfig, self::configPro__premium_only() );
        }
        self::registerMany( $mergedConfig );
    }

}
