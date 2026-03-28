<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Domain\Policy;

final class FeedTitleRules {
	private const AUTO_TITLE_PLACEHOLDERS = [
		'name my feed',
		'untitled feed',
		'new feed',
	];

	public function shouldGenerateTitle( string $title ): bool {
		$title = trim( $title );
		if ( $title === '' ) {
			return true;
		}

		return in_array( strtolower( $title ), self::AUTO_TITLE_PLACEHOLDERS, true );
	}

	public function buildBaseTitle( string $sourceLabel, string $layoutLabel ): string {
		$sourceLabel = trim( $sourceLabel );
		if ( $sourceLabel === '' ) {
			$sourceLabel = 'Sources';
		}

		$layoutLabel = trim( $layoutLabel );
		if ( $layoutLabel === '' ) {
			$layoutLabel = 'Grid';
		}

		return 'Feed - ' . $sourceLabel . ' - ' . $layoutLabel;
	}

	/**
	 * @param string $baseTitle
	 * @param array  $existingTitles
	 */
	public function makeUniqueTitle( string $baseTitle, array $existingTitles ): string {
		$baseTitle = trim( $baseTitle );
		if ( $baseTitle === '' ) {
			$baseTitle = 'Feed';
		}

		$normalizedExisting = array_map(
			static function ( $title ): string {
				return strtolower( trim( (string) $title ) );
			},
			$existingTitles
		);

		$baseKey = strtolower( $baseTitle );
		if ( ! in_array( $baseKey, $normalizedExisting, true ) ) {
			return $baseTitle;
		}

		$i = 1;
		while ( true ) {
			$candidate = $baseTitle . ' - ' . $i;
			if ( ! in_array( strtolower( $candidate ), $normalizedExisting, true ) ) {
				return $candidate;
			}

			++$i;
		}
	}
}
