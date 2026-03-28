<?php
declare(strict_types=1);

namespace Inavii\Instagram\Front;

class Shortcode {

	/** @var string Shortcode tag. */
	private string $tag = 'inavii-feed';

	//TODO inavii-feed-instagram

	private Render $render;

	public function __construct( Render $render ) {
		$this->render = $render;
	}

	public function init(): void {
		add_shortcode( $this->tag, [ $this, 'render' ] );
	}

	/**
	 * @param array       $atts Shortcode attributes.
	 * @param string|null $content Shortcode content (if any).
	 * @param string      $tag The shortcode tag being processed.
	 * @return string Rendered HTML output for the shortcode.
	 */
	public function render( $atts, ?string $content = null, string $tag = '' ): string {
		$atts = shortcode_atts(
			[
				'id'      => '',
				'feed_id' => '',
			],
			is_array( $atts ) ? $atts : [],
			$tag !== '' ? $tag : $this->tag
		);

		$rawId   = $atts['id'] !== '' ? $atts['id'] : $atts['feed_id'];
		$feed_id = trim( (string) $rawId );

		if ( $feed_id === '' ) {
			return '';
		}

		return $this->render->render( $feed_id );
	}
}
