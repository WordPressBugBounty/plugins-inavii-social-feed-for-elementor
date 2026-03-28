<?php
declare(strict_types=1);

namespace Inavii\Instagram\Config;

/**
 * Central plugin environment/config values.
 */
final class Env {

	/** @var string Plugin base URL */
	public static string $base_url = '';
	/** @var string Plugin main file path */
	public static string $file = '';
	/** @var string Plugin folder name */
	public static string $plugin_folder = '';
	/** @var string Plugin path */
	public static string $path = '';
	/** @var string Plugin assets path */
	public static string $assets_path = '';
	/** @var string Plugin assets URL */
	public static string $assets_url = '';
	/** @var string Plugin media directory (uploads) */
	public static string $media_dir = '';
	/** @var string Plugin media URL (uploads) */
	public static string $media_url = '';
	/** @var string WordPress uploads base dir */
	public static string $uploads_dir = '';
	/** @var string WordPress uploads base URL */
	public static string $uploads_url = '';

	/**
	 * Init env values.
	 *
	 * @param string $file Main plugin file.
	 */
	public static function init( string $file ): void {
		$upload_dir = wp_get_upload_dir();

		self::$file          = $file;
		self::$plugin_folder = dirname( plugin_basename( self::$file ) );
		self::$path          = dirname( self::$file );
		self::$base_url      = plugins_url( '', $file );
		self::$assets_path   = self::$path . '/assets';
		self::$assets_url    = plugins_url( '/assets', $file );
		self::$media_dir     = $upload_dir['basedir'] . '/inavii-social-feed';
		self::$media_url     = $upload_dir['baseurl'] . '/inavii-social-feed';
		self::$uploads_dir   = $upload_dir['basedir'] ?? '';
		self::$uploads_url   = $upload_dir['baseurl'] ?? '';
	}
}
