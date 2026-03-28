<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy\Migration;

final class LegacyFeedLayoutMap {
	/**
	 * @return array{view:string,viewVariant:string,mode:string}|null
	 */
	public static function toV3( string $layout ): ?array {
		$key = self::normalize( $layout );
		if ( $key === '' ) {
			return null;
		}

		$map = [
			'highlight'          => [
				'view'        => 'highlight',
				'viewVariant' => 'highlight_discovery',
				'mode'        => 'highlight',
			],
			'highlight-super'    => [
				'view'        => 'highlight',
				'viewVariant' => 'highlight_super',
				'mode'        => 'highlight',
			],
			'cards'              => [
				'view'        => 'masonry',
				'viewVariant' => 'masonry_cards',
				'mode'        => 'masonry',
			],
			'masonry-horizontal' => [
				'view'        => 'masonry',
				'viewVariant' => 'masonry',
				'mode'        => 'masonry',
			],
			'masonry-vertical'   => [
				'view'        => 'masonry',
				'viewVariant' => 'masonry',
				'mode'        => 'masonry',
			],
			'highlight-focus'    => [
				'view'        => 'highlight',
				'viewVariant' => 'highlight',
				'mode'        => 'highlight',
			],
			'slider'             => [
				'view'        => 'slider',
				'viewVariant' => 'slider',
				'mode'        => 'slider',
			],
			'grid'               => [
				'view'        => 'grid',
				'viewVariant' => 'grid',
				'mode'        => 'grid',
			],
			'wave'               => [
				'view'        => 'grid',
				'viewVariant' => 'grid_wave',
				'mode'        => 'grid',
			],
			'wave-grid'          => [
				'view'        => 'grid',
				'viewVariant' => 'grid_wave_grid',
				'mode'        => 'grid',
			],
			'row'                => [
				'view'        => 'grid',
				'viewVariant' => 'grid_row',
				'mode'        => 'grid',
			],
			'gallery'            => [
				'view'        => 'grid',
				'viewVariant' => 'grid_gallery',
				'mode'        => 'grid',
			],
		];

		return $map[ $key ] ?? null;
	}

	/**
	 * @param array $feedLayout
	 */
	public static function toLegacy( array $feedLayout ): ?string {
		$variant = self::normalizeKey( $feedLayout['viewVariant'] ?? null );
		$view    = self::normalizeKey( $feedLayout['view'] ?? null );
		$mode    = self::normalizeKey( $feedLayout['mode'] ?? null );

		$key = $variant !== '' ? $variant : ( $view !== '' ? $view : $mode );
		if ( $key === '' ) {
			return null;
		}

		$map = [
			'highlight_discovery' => 'highlight',
			'highlight_super'     => 'highlight-super',
			'highlight'           => 'highlight-focus',
			'slider'              => 'slider',
			'grid'                => 'grid',
			'grid_wave'           => 'wave',
			'grid_wave_grid'      => 'wave-grid',
			'grid_row'            => 'row',
			'grid_gallery'        => 'gallery',
			'masonry_cards'       => 'cards',
			'masonry'             => 'masonry-horizontal',
		];

		return $map[ $key ] ?? null;
	}

	private static function normalize( string $layout ): string {
		$layout = strtolower( trim( $layout ) );
		if ( $layout === '' ) {
			return '';
		}

		$layout = str_replace( '_', '-', $layout );
		$layout = preg_replace( '/\s+/', '-', $layout );

		return is_string( $layout ) ? trim( $layout, '-' ) : '';
	}

	/**
	 * @param mixed $value
	 */
	private static function normalizeKey( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( strtolower( (string) $value ) );
		if ( $value === '' ) {
			return '';
		}

		$value = str_replace( '-', '_', $value );
		$value = preg_replace( '/\s+/', '_', $value );

		return is_string( $value ) ? trim( $value, '_' ) : '';
	}
}
