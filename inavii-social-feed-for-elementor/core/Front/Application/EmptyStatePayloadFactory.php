<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Application;

final class EmptyStatePayloadFactory {
	private const DEFAULT_EMPTY_PRESET = 'default_feed_shell';

	public function build( int $feedId, string $title, string $text, string $preset = self::DEFAULT_EMPTY_PRESET ): array {
		return array_merge(
			$this->createBasePayload( $feedId ),
			[
				'emptyState' => [
					'title'  => trim( $title ),
					'text'   => trim( $text ),
					'preset' => $this->resolvePresetValue( $preset ),
				],
			]
		);
	}

	private function resolvePresetValue( string $value ): string {
		$value = trim( $value );

		if ( $value !== '' ) {
			return $value;
		}

		return self::DEFAULT_EMPTY_PRESET;
	}

	private function createBasePayload( int $feedId ): array {
		return [
			'options' => [
				'id'       => $feedId,
				'settings' => [],
			],
			'media'   => [],
		];
	}
}
