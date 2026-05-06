<?php
declare(strict_types=1);

namespace Inavii\Instagram\Di;

use RuntimeException;

/**
 * Resolves DI symbols for both unscoped (dev) and scoped (package) vendors.
 */
final class DiVendorResolver {
	private const SCOPED_PREFIX         = 'InaviiSocialFeedVendor\\';
	private const SCOPED_PACKAGE_MAP    = [
		'DI\\'                           => 'vendor-prefixed/php-di/php-di/src',
		'Invoker\\'                      => 'vendor-prefixed/php-di/invoker/src',
		'PhpDocReader\\'                 => 'vendor-prefixed/php-di/phpdoc-reader/src/PhpDocReader',
		'Psr\\Container\\'               => 'vendor-prefixed/psr/container/src',
		'Laravel\\SerializableClosure\\' => 'vendor-prefixed/laravel/serializable-closure/src',
	];
	private const SCOPED_FUNCTIONS_FILE = 'vendor-prefixed/php-di/php-di/src/functions.php';

	/** @var bool */
	private static $scopedAutoloadRegistered = false;

	/** @var bool */
	private static $scopedFunctionsLoaded = false;

	public static function containerBuilderClass(): string {
		return self::resolveClass( 'DI\\ContainerBuilder' );
	}

	/**
	 * @param string $entry
	 * @return mixed
	 */
	public static function autowire( string $entry ) {
		return self::callFunction( 'DI\\autowire', [ $entry ] );
	}

	/**
	 * @param callable $factory
	 * @return mixed
	 */
	public static function factory( callable $factory ) {
		return self::callFunction( 'DI\\factory', [ $factory ] );
	}

	private static function resolveClass( string $className ): string {
		self::registerScopedAutoloader();

		$scopedClass = self::SCOPED_PREFIX . $className;
		if ( class_exists( $scopedClass ) || interface_exists( $scopedClass ) ) {
			return $scopedClass;
		}

		if ( class_exists( $className ) || interface_exists( $className ) ) {
			return $className;
		}

		throw new RuntimeException(
			sprintf( 'Missing DI class: %s (scoped: %s)', $className, $scopedClass )
		);
	}

	/**
	 * @param string           $functionName
	 * @param array<int,mixed> $args
	 * @return mixed
	 */
	private static function callFunction( string $functionName, array $args ) {
		self::registerScopedAutoloader();

		$scopedFunctionName = self::SCOPED_PREFIX . $functionName;
		if ( function_exists( $scopedFunctionName ) ) {
			return call_user_func_array( $scopedFunctionName, $args );
		}

		if ( function_exists( $functionName ) ) {
			return call_user_func_array( $functionName, $args );
		}

		throw new RuntimeException(
			sprintf( 'Missing DI function: %s (scoped: %s)', $functionName, $scopedFunctionName )
		);
	}

	private static function registerScopedAutoloader(): void {
		if ( self::$scopedAutoloadRegistered ) {
			return;
		}

		self::$scopedAutoloadRegistered = true;
		$baseDir                        = self::baseDir();
		$map                            = [];
		foreach ( self::SCOPED_PACKAGE_MAP as $namespace => $path ) {
			$map[ self::SCOPED_PREFIX . $namespace ] = $baseDir . '/' . $path . '/';
		}

		spl_autoload_register(
			static function ( string $className ) use ( $map ): void {
				foreach ( $map as $prefix => $directory ) {
					if ( strpos( $className, $prefix ) !== 0 ) {
						continue;
					}

					$relativeClass = substr( $className, strlen( $prefix ) );
					$relativePath  = str_replace( '\\', '/', $relativeClass ) . '.php';
					$filePath      = $directory . $relativePath;
					if ( is_file( $filePath ) ) {
						require_once $filePath;
					}

					return;
				}
			}
		);

		self::loadScopedFunctions();
	}

	private static function loadScopedFunctions(): void {
		if ( self::$scopedFunctionsLoaded ) {
			return;
		}

		self::$scopedFunctionsLoaded = true;
		$scopedFunctionsFile         = self::scopedFunctionsPath();
		if ( is_file( $scopedFunctionsFile ) ) {
			require_once $scopedFunctionsFile;
		}
	}

	private static function scopedFunctionsPath(): string {
		return self::baseDir() . '/' . self::SCOPED_FUNCTIONS_FILE;
	}

	private static function baseDir(): string {
		if ( defined( 'INAVII_INSTAGRAM_DIR' ) ) {
			return rtrim( (string) INAVII_INSTAGRAM_DIR, '/\\' );
		}

		return dirname( __DIR__, 2 );
	}
}
