<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi;

use Inavii\Instagram\RestApi\EndPoints\Account\AccountController;
use Inavii\Instagram\RestApi\EndPoints\Media\MediaController;
use Inavii\Instagram\RestApi\EndPoints\Cron\CronController;
use Inavii\Instagram\RestApi\EndPoints\Feed\FeedController;
use Inavii\Instagram\RestApi\EndPoints\Front\FrontController;
use Inavii\Instagram\RestApi\EndPoints\Settings\SettingsController;
use Inavii\Instagram\RestApi\EndPoints\System\HealthController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use function Inavii\Instagram\Di\container;

final class RegisterRestApiV2 {
	private const API_NAMESPACE                      = 'inavii/v2';
	private const CRON_PING_REQUEST_TRANSIENT        = 'inavii_cron_ping_request_lock';
	private const DEFAULT_CRON_PING_REQUEST_INTERVAL = 60;

	private static function config(): array {
		return [
			[
				'route'               => 'accounts',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( AccountController::class ), 'all' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'accounts/(?P<id>\\d+)',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( AccountController::class ), 'get' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'accounts/connect',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( AccountController::class ), 'connect' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'accounts/(?P<id>\\d+)',
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ container()->get( AccountController::class ), 'delete' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'media/import',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( MediaController::class ), 'import' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( FeedController::class ), 'all' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds/(?P<id>\\d+)',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( FeedController::class ), 'get' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds/(?P<id>\\d+)/front',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( FeedController::class ), 'front' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( FeedController::class ), 'create' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds/preview',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( FeedController::class ), 'preview' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds/(?P<id>\\d+)',
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ container()->get( FeedController::class ), 'update' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds/(?P<id>\\d+)',
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ container()->get( FeedController::class ), 'delete' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'feeds/(?P<id>\\d+)/clear-cache',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( FeedController::class ), 'clearCache' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'cron/ping',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( CronController::class ), 'ping' ],
				'permission_callback' => [ self::class, 'cronPingPermission' ],
			],
			[
				'route'               => 'health/issues',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( HealthController::class ), 'issues' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'settings',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( SettingsController::class ), 'get' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'settings',
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ container()->get( SettingsController::class ), 'update' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'settings/delete-all',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( SettingsController::class ), 'deleteAll' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'settings/clear-cache',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( SettingsController::class ), 'clearCache' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'settings/retry-db',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( SettingsController::class ), 'retryDatabase' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'settings/ui-version/v2',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( SettingsController::class ), 'switchToV2' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'settings/ui-version/v3',
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ container()->get( SettingsController::class ), 'switchToV3' ],
				'permission_callback' => [ self::class, 'adminPermission' ],
			],
			[
				'route'               => 'front/feeds/(?P<id>\\d+)',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( FrontController::class ), 'payload' ],
				'permission_callback' => [ self::class, 'frontPermission' ],
			],
			[
				'route'               => 'front/feeds/(?P<id>\\d+)/media',
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ container()->get( FrontController::class ), 'media' ],
				'permission_callback' => [ self::class, 'frontPermission' ],
			],
		];
	}

	public static function registerRoute(): void {
		foreach ( self::config() as $config ) {
			$permissionCallback = $config['permission_callback'] ?? [ self::class, 'denyPermission' ];

			register_rest_route(
				self::API_NAMESPACE,
				$config['route'],
				[
					'methods'             => $config['methods'],
					'callback'            => $config['callback'],
					'permission_callback' => $permissionCallback,
				]
			);
		}
	}

	public static function adminPermission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @return bool|WP_Error
	 */
	public static function cronPingPermission( WP_REST_Request $request ) {
		if ( self::adminPermission() ) {
			return true;
		}

		$keyService = container()->get( PublicRequestKey::class );
		$key        = self::requestKey( $request, 'X-Inavii-Cron-Key' );
		if ( ! $keyService->verifyCronPingKey( $key ) ) {
			return new WP_Error(
				'inavii_invalid_public_cron_key',
				'Invalid public cron key.',
				[ 'status' => 403 ]
			);
		}

		$allowed = (bool) apply_filters( 'inavii/social-feed/cron/ping/public', true, $request );
		if ( ! $allowed ) {
			return false;
		}

		if ( self::isCronPingRateLimited() ) {
			return new WP_Error(
				'inavii_public_cron_ping_rate_limited',
				'Cron ping temporarily rate limited.',
				[ 'status' => 429 ]
			);
		}

		self::markCronPingRequest();

		return true;
	}

	/**
	 * @return bool|WP_Error
	 */
	public static function frontPermission( WP_REST_Request $request ) {
		if ( self::adminPermission() ) {
			return true;
		}

		$feedId = absint( $request->get_param( 'id' ) );
		$key    = self::requestKey( $request, 'X-Inavii-Feed-Key' );

		$keyService = container()->get( PublicRequestKey::class );
		if ( ! $keyService->verifyFeedKey( $feedId, $key ) ) {
			return new WP_Error(
				'inavii_invalid_public_feed_key',
				'Invalid public feed key.',
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	public static function denyPermission(): bool {
		return false;
	}

	public static function publicPermission(): bool {
		return true;
	}

	private static function requestKey( WP_REST_Request $request, string $header ): string {
		return trim( (string) $request->get_header( $header ) );
	}

	private static function isCronPingRateLimited(): bool {
		return get_transient( self::CRON_PING_REQUEST_TRANSIENT ) !== false;
	}

	private static function markCronPingRequest(): void {
		set_transient( self::CRON_PING_REQUEST_TRANSIENT, 1, self::cronPingRequestInterval() );
	}

	private static function cronPingRequestInterval(): int {
		$ttl = (int) apply_filters(
			'inavii/social-feed/cron/ping/request_interval',
			self::DEFAULT_CRON_PING_REQUEST_INTERVAL
		);

		return $ttl > 0 ? $ttl : self::DEFAULT_CRON_PING_REQUEST_INTERVAL;
	}
}
