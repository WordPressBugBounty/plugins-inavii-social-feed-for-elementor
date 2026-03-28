<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Integration;

final class LegacyIntegrationRuntime {
	private const LEGACY_BASE_DIR = 'includes/Legacy/Integration/';

	private static bool $registered = false;

	public function register(): void {
		if ( self::$registered ) {
			return;
		}

		self::$registered = true;

		add_filter( 'timber/locations', [ self::class, 'registerTwigLocations' ] );
	}

	public static function registerTwigLocations( $locations ): array {
		if ( ! is_array( $locations ) ) {
			$locations = [];
		}

		$mainNamespace = class_exists( '\Timber\Loader' ) ? \Timber\Loader::MAIN_NAMESPACE : '__main__';
		$flatLocations = [];

		foreach ( $locations as $key => $value ) {
			if ( is_int( $key ) && is_string( $value ) && $value !== '' ) {
				$flatLocations[] = $value;
				unset( $locations[ $key ] );
			}
		}

		if ( ! isset( $locations[ $mainNamespace ] ) || ! is_array( $locations[ $mainNamespace ] ) ) {
			$locations[ $mainNamespace ] = [];
		}

		if ( $flatLocations !== [] ) {
			$locations[ $mainNamespace ] = array_values(
				array_unique(
					array_merge( $flatLocations, $locations[ $mainNamespace ] )
				)
			);
		}

		$viewsBase = rtrim( INAVII_INSTAGRAM_DIR . self::LEGACY_BASE_DIR . 'Views', '/\\' );
		$viewsDir  = $viewsBase . DIRECTORY_SEPARATOR . 'view';

		if ( ! in_array( $viewsBase, $locations[ $mainNamespace ], true ) ) {
			$locations[ $mainNamespace ][] = $viewsBase;
		}

		if ( ! in_array( $viewsDir, $locations[ $mainNamespace ], true ) ) {
			$locations[ $mainNamespace ][] = $viewsDir;
		}

		return $locations;
	}
}
