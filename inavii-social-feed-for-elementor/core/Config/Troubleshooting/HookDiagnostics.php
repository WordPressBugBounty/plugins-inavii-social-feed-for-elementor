<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config\Troubleshooting;

use Inavii\Instagram\Logger\Logger;

final class HookDiagnostics {
	/**
	 * @var array
	 */
	private array $hooks;

	/**
	 * @param array|null $hooks
	 */
	public function __construct( ?array $hooks = null ) {
		$this->hooks = $hooks ?? [
			[
				'hook'     => 'inavii/social-feed/account/connected',
				'label'    => 'Account connected',
				'required' => true,
			],
			[
				'hook'     => 'inavii/social-feed/account/deleted',
				'label'    => 'Account deleted',
				'required' => true,
			],
			[
				'hook'     => 'inavii/social-feed/feed/created',
				'label'    => 'Feed created',
				'required' => true,
			],
			[
				'hook'     => 'inavii/social-feed/feed/updated',
				'label'    => 'Feed updated',
				'required' => true,
			],
			[
				'hook'     => 'inavii/social-feed/feed/deleted',
				'label'    => 'Feed deleted',
				'required' => true,
			],
			[
				'hook'     => 'inavii/social-feed/media/sync/started',
				'label'    => 'Media sync started',
				'required' => false,
			],
			[
				'hook'     => 'inavii/social-feed/media/sync/finished',
				'label'    => 'Media sync finished',
				'required' => false,
			],
			[
				'hook'     => 'inavii/social-feed/media/sync/error',
				'label'    => 'Media sync error',
				'required' => false,
			],
		];
	}

	/**
	 * @return array
	 */
	public function status(): array {
		$rows = [];
		foreach ( $this->hooks as $hook ) {
			$name     = (string) $hook['hook'];
			$required = isset( $hook['required'] ) ? (bool) $hook['required'] : true;
			$rows[]   = [
				'hook'          => $name,
				'label'         => (string) $hook['label'],
				'has_listeners' => has_action( $name ) > 0,
				'required'      => $required,
			];
		}

		return $rows;
	}

	/**
	 * Log a warning for missing listeners (once per hook).
	 */
	public function reportMissing(): void {
		foreach ( $this->hooks as $hook ) {
			$name     = (string) $hook['hook'];
			$required = isset( $hook['required'] ) ? (bool) $hook['required'] : true;

			if ( ! $required ) {
				continue;
			}

			if ( has_action( $name ) > 0 ) {
				continue;
			}

			if ( isset( self::$reported[ $name ] ) ) {
				continue;
			}

			self::$reported[ $name ] = true;
			Logger::warning( 'hooks', 'Hook has no listeners: ' . $name );
		}
	}

	/**
	 * @var array
	 */
	private static array $reported = [];
}
