<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Domain\Policy;

final class HashtagLabelPolicy {
	/**
	 * @param string[] $tags
	 */
	public function buildLabel( array $tags ): string {
		$tags = array_values(
			array_filter(
				$tags,
				static function ( $tag ): bool {
					return is_string( $tag ) && $tag !== '';
				}
			)
		);

		if ( $tags === [] ) {
			return '#instagram';
		}

		return implode(
			' ',
			array_map(
				static function ( string $tag ): string {
					return '#' . ltrim( $tag, '#' );
				},
				$tags
			)
		);
	}
}
