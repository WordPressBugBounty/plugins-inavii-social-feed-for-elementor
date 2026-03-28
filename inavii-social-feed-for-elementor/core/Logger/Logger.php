<?php
declare(strict_types=1);

namespace Inavii\Instagram\Logger;

use Inavii\Instagram\Logger\Storage\LoggerRepository;

final class Logger {

	public const LEVEL_DEBUG   = 'debug';
	public const LEVEL_INFO    = 'info';
	public const LEVEL_WARNING = 'warning';
	public const LEVEL_ERROR   = 'error';

	private static ?LoggerRepository $repository = null;

	public static function debug( string $component, string $message, array $context = [] ): void {
		self::write( self::LEVEL_DEBUG, $component, $message, $context );
	}

	public static function info( string $component, string $message, array $context = [] ): void {
		self::write( self::LEVEL_INFO, $component, $message, $context );
	}

	public static function warning( string $component, string $message, array $context = [] ): void {
		self::write( self::LEVEL_WARNING, $component, $message, $context );
	}

	public static function error( string $component, string $message, array $context = [] ): void {
		self::write( self::LEVEL_ERROR, $component, $message, $context );
	}

	/**
	 * @return array
	 */
	public static function latest( int $limit = 100 ): array {
		$limit = max( 1, $limit );

		$repo = self::repo();
		if ( $repo === null ) {
			return [];
		}

		return $repo->latest( $limit );
	}

	public static function clear(): void {
		$repo = self::repo();
		if ( $repo === null ) {
			return;
		}

		$repo->clear();
	}

	private static function write( string $level, string $component, string $message, array $context ): void {
		if ( ! self::enabled() ) {
			return;
		}

		$repo = self::repo();
		if ( $repo === null ) {
			return;
		}

		$message     = self::redact( $message );
		$context     = self::sanitizeContext( $context );
		$contextJson = $context === [] ? null : wp_json_encode( $context );
		$createdAt   = current_time( 'mysql' );

		$result = $repo->insert( $level, $component, $message, $contextJson, $createdAt );
		if ( ! $result ) {
			// Last-resort fallback (avoid recursion).
			error_log( '[Inavii] Logger insert failed.' );
			return;
		}

		$limit = (int) apply_filters( 'inavii/social-feed/logs/max_entries', 100 );
		$repo->trimTo( $limit );
	}

	private static function repo(): ?LoggerRepository {
		if ( self::$repository instanceof LoggerRepository ) {
			return self::$repository;
		}

		if ( function_exists( '\\Inavii\\Instagram\\Di\\container' ) ) {
			try {
				$repo = \Inavii\Instagram\Di\container()->get( LoggerRepository::class );
				if ( $repo instanceof LoggerRepository ) {
					self::$repository = $repo;
					return $repo;
				}
			} catch ( \Throwable $e ) {
				return null;
			}
		}

		return null;
	}

	private static function enabled(): bool {
		return (bool) apply_filters( 'inavii/social-feed/logs/enabled', true );
	}

	private static function sanitizeContext( array $context ): array {
		$sensitiveKeys = [
			'access_token',
			'token',
			'refresh_token',
			'client_secret',
			'secret',
			'auth',
		];

		foreach ( $context as $key => $value ) {
			if ( is_string( $key ) && in_array( strtolower( $key ), $sensitiveKeys, true ) ) {
				$context[ $key ] = '***';
				continue;
			}

			if ( is_array( $value ) ) {
				$context[ $key ] = self::sanitizeContext( $value );
			}
		}

		return $context;
	}

	private static function redact( string $message ): string {
		if ( $message === '' ) {
			return $message;
		}

		$patterns = [
			'/(access_token|token|refresh_token)(\\s*[:=]\\s*)([A-Za-z0-9\\-_\\.]+)/i',
			'/\\bEA[A-Za-z0-9]{8,}\\b/',
		];

		foreach ( $patterns as $pattern ) {
			$message = preg_replace( $pattern, '$1$2***', $message );
		}

		return (string) $message;
	}
}
