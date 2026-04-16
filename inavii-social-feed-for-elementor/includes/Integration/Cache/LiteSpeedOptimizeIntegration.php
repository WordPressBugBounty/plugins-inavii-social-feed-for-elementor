<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Integration\Cache;

final class LiteSpeedOptimizeIntegration {
	private const EXCLUDES = [
		'inavii-',
		'react',
	];

	public function __construct() {
		add_filter( 'litespeed_optimize_js_excludes', [ $this, 'excludeScripts' ] );
		add_filter( 'rest_pre_serve_request', [ $this, 'disableRestCache' ], 10, 4 );
	}

	/**
	 * @param string[] $excludes
	 * @return string[]
	 */
	public function excludeScripts( array $excludes ): array {
		$merged = array_merge( $excludes, self::EXCLUDES );
		return array_values( array_unique( $merged ) );
	}

	/**
	 * @param bool             $served
	 * @param mixed            $result
	 * @param \WP_REST_Request $request
	 * @param \WP_REST_Server  $server
	 *
	 * @return bool
	 */
	public function disableRestCache( bool $served, $result, \WP_REST_Request $request, \WP_REST_Server $server ): bool {
		$route = $request->get_route();
		if ( ! $this->isInaviiRestRoute( $route ) ) {
			return $served;
		}

		// Tell LiteSpeed plugin control layer that this request must not be cached.
		if ( function_exists( 'do_action' ) ) {
			//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed control hook.
			do_action( 'litespeed_control_set_nocache', 'Inavii REST API endpoint' );
		}

		// Additional hard stop recognized by LiteSpeed control logic.
		if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
			//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- LiteSpeed control constant.
			define( 'LSCACHE_NO_CACHE', true );
		}

		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		}

		return $served;
	}

	private function isInaviiRestRoute( string $route ): bool {
		return 0 === strpos( $route, '/inavii/v1' ) || 0 === strpos( $route, '/inavii/v2' );
	}
}
