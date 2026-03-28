<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Application;

use Inavii\Instagram\FrontIndex\Application\Meta\FooterMetaBuilder;
use Inavii\Instagram\FrontIndex\Application\Meta\HeaderMetaBuilder;

final class FrontIndexMetaBuilder {
	private HeaderMetaBuilder $headerBuilder;
	private FooterMetaBuilder $footerBuilder;

	public function __construct(
		HeaderMetaBuilder $headerBuilder,
		FooterMetaBuilder $footerBuilder
	) {
		$this->headerBuilder = $headerBuilder;
		$this->footerBuilder = $footerBuilder;
	}

	/**
	 * @param array $feed
	 * @param int   $total
	 *
	 * @return array
	 */
	public function build( array $feed, int $total ): array {
		$design = $this->extractDesign( $feed );

		$meta = [
			'options' => $feed,
			'total'   => $total,
		];

		$meta = array_merge( $meta, $this->headerBuilder->build( $feed, $design ) );

		return array_merge( $meta, $this->footerBuilder->build( $feed, $design ) );
	}

	/**
	 * @param array $feed
	 *
	 * @return array
	 */
	private function extractDesign( array $feed ): array {
		$settings = isset( $feed['settings'] ) && is_array( $feed['settings'] ) ? $feed['settings'] : [];

		return isset( $settings['design'] ) && is_array( $settings['design'] ) ? $settings['design'] : [];
	}
}
