<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Integration\Elementor;

use Elementor\Plugin as ElementorPlugin;
use Inavii\Instagram\Config\Plugin as InaviiPlugin;

final class ElementorWidget {
	public function __construct() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		add_action( 'elementor/elements/categories_registered', [ $this, 'registerCategory' ] );

		if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '<' ) ) {
			add_action( 'elementor/widgets/widgets_registered', [ $this, 'registerWidgets' ] );
		} else {
			add_action( 'elementor/widgets/register', [ $this, 'registerWidgets' ] );
		}
	}

	/**
	 * @param mixed $elementsManager
	 */
	public function registerCategory( $elementsManager ): void {
		if ( ! $this->shouldRegister() ) {
			return;
		}

		$elementsManager->add_category(
			'inavii-social-feed',
			[
				'title' => __( 'Inavii Social Feed', 'inavii-social-feed' ),
				'icon'  => 'fa fa-plug',
			]
		);
	}

	public function registerWidgets(): void {
		if ( ! $this->shouldRegister() ) {
			return;
		}

		$widgetsManager = ElementorPlugin::instance()->widgets_manager;
		$widget         = new InaviiFeedWidget();

		if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '<' ) ) {
			$widgetsManager->register_widget_type( $widget );
		} else {
			$widgetsManager->register( $widget );
		}
	}

	private function shouldRegister(): bool {
		return ! InaviiPlugin::isLegacyUi();
	}
}
