<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Application\UseCase;

use Inavii\Instagram\Front\Application\FrontIndexReader;
use Inavii\Instagram\Front\Domain\Policy\PreloadMediaPolicy;

final class GetFrontPayload {
	private FrontIndexReader $index;
	private PreloadMediaPolicy $preloadPolicy;

	public function __construct(
		FrontIndexReader $index,
		PreloadMediaPolicy $preloadPolicy
	) {
		$this->index         = $index;
		$this->preloadPolicy = $preloadPolicy;
	}

	public function handle( int $feedId, int $limit = 0, int $offset = 0 ): array {
		if ( $feedId <= 0 ) {
			return [];
		}

		if ( $offset < 0 ) {
			$offset = 0;
		}

		$index = $this->index->load( $feedId );
		if ( $index === [] ) {
			return [];
		}

		$meta         = isset( $index['meta'] ) && is_array( $index['meta'] ) ? $index['meta'] : [];
		$options      = isset( $meta['options'] ) && is_array( $meta['options'] ) ? $meta['options'] : [];
		$media        = isset( $index['media'] ) && is_array( $index['media'] ) ? $index['media'] : [];
		$profiles     = $this->extractProfilesMap( $meta );
		$preloadLimit = $this->preloadPolicy->resolveLimit( $meta );
		if ( $options === [] ) {
			return [];
		}

		$payload = [
			'options'  => $options,
			'media'    => [],
			'profiles' => $profiles,
		];

		if ( isset( $meta['header'] ) && is_array( $meta['header'] ) ) {
			$payload['header'] = $meta['header'];
		}
		if ( array_key_exists( 'showHeader', $meta ) ) {
			$payload['showHeader'] = (bool) $meta['showHeader'];
		}
		if ( isset( $meta['footer'] ) && is_array( $meta['footer'] ) ) {
			$payload['footer'] = $meta['footer'];
		}
		if ( array_key_exists( 'showFooter', $meta ) ) {
			$payload['showFooter'] = (bool) $meta['showFooter'];
		}
		if ( isset( $meta['total'] ) ) {
			$payload['total'] = (int) $meta['total'];
		}

		$payload['media'] = $this->preloadPolicy->resolveMediaSlice( $options, $media, $limit, $offset, $preloadLimit );

		return $payload;
	}

	private function extractProfilesMap( array $meta ): array {
		$profiles = isset( $meta['profiles'] ) && is_array( $meta['profiles'] ) ? $meta['profiles'] : [];
		if ( $profiles === [] ) {
			return [];
		}

		$out = [];
		foreach ( $profiles as $ref => $profile ) {
			if ( ! is_string( $ref ) || trim( $ref ) === '' ) {
				continue;
			}
			if ( ! is_array( $profile ) ) {
				continue;
			}

			$out[ $ref ] = $profile;
		}

		return $out;
	}
}
