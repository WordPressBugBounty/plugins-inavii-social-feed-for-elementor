<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Integration\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\Feed\Domain\Feed;

use function Inavii\Instagram\Di\container;

final class InaviiFeedWidget extends Widget_Base {
	public function get_name(): string {
		return 'inavii-feed-widget-root';
	}

	public function get_title(): string {
		return esc_html__( 'Inavii Social Feed', 'inavii-social-feed' );
	}

	public function get_icon(): string {
		return 'eicon-instagram-post';
	}

	public function get_categories(): array {
		return [ 'inavii-social-feed' ];
	}

	public function get_keywords(): array {
		return [ 'instagram', 'feed', 'social', 'inavii' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'section_inavii_feed',
			[
				'label' => esc_html__( 'Instagram Feed', 'inavii-social-feed' ),
			]
		);

		$options = $this->feedOptions();

		if ( $options === [] ) {
			$this->add_control(
				'no_feeds_message',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => wp_kses_post(
						sprintf(
							/* translators: %s: link to Inavii settings page. */
							__( 'No feeds found. Create your first feed <a href="%s" target="_blank" rel="noopener noreferrer">here</a>.', 'inavii-social-feed' ),
							esc_url( admin_url( 'admin.php?page=inavii-instagram-settings' ) )
						)
					),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				]
			);
		} else {
			$this->add_control(
				'feed_id',
				[
					'label'   => esc_html__( 'Select Feed', 'inavii-social-feed' ),
					'type'    => Controls_Manager::SELECT,
					'options' => $options,
					'default' => 'select-feed',
				]
			);
		}

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$feedId   = $this->resolveFeedIdSetting( $settings['feed_id'] ?? null );

		if ( $feedId <= 0 ) {
			echo $this->renderPlaceholder(
				esc_html__( 'Select a feed to preview', 'inavii-social-feed' ),
				esc_html__( 'Choose a feed in widget settings to display it here.', 'inavii-social-feed' )
			);
			return;
		}

		echo do_shortcode( sprintf( '[inavii-feed id="%d"]', $feedId ) );
	}

	private function renderPlaceholder( string $title, string $text ): string {
		try {
			do_action( 'inavii/social-feed/front/enqueue_front_app_assets' );

			/** @var \Inavii\Instagram\Front\Render $renderer */
			$renderer = container()->get( \Inavii\Instagram\Front\Render::class );
			return $renderer->renderEmptyState( $title, $text );
		} catch ( \Throwable $e ) {
			return '<div class="inavii-widget-empty">' . esc_html( $title ) . '</div>';
		}
	}

	/**
	 * @return array<string,string>
	 */
	private function feedOptions(): array {
		try {
			/** @var FeedRepository $repository */
			$repository = container()->get( FeedRepository::class );
			$feeds      = $repository->all();
		} catch ( \Throwable $e ) {
			return [];
		}

		$items = [];
		foreach ( $feeds as $feed ) {
			if ( ! $feed instanceof Feed ) {
				continue;
			}

			$feedId = (int) $feed->id();
			if ( $feedId <= 0 ) {
				continue;
			}

			$title = trim( (string) $feed->title() );
			if ( $title === '' ) {
				$title = 'Feed #' . $feedId;
			}

			$post       = get_post( $feedId );
			$createdAt  = '';
			if ( $post instanceof \WP_Post ) {
				$createdAt = (string) ( $post->post_date_gmt ?: $post->post_date );
			}

			$items[] = [
				'id'        => $feedId,
				'title'     => $title,
				'createdAt' => $createdAt,
			];
		}

		if ( $items === [] ) {
			return [];
		}

		usort(
			$items,
			static function ( array $left, array $right ): int {
				$leftTimestamp  = strtotime( (string) $left['createdAt'] ) ?: 0;
				$rightTimestamp = strtotime( (string) $right['createdAt'] ) ?: 0;

				if ( $leftTimestamp === $rightTimestamp ) {
					return (int) $right['id'] <=> (int) $left['id'];
				}

				return $rightTimestamp <=> $leftTimestamp;
			}
		);

		$options = [];
		foreach ( $items as $item ) {
			$options[ $this->buildFeedOptionKey( (int) $item['id'] ) ] = (string) $item['title'];
		}

		return [ 'select-feed' => esc_html__( 'Select feed', 'inavii-social-feed' ) ] + $options;
	}

	/**
	 * @param mixed $value
	 */
	private function resolveFeedIdSetting( $value ): int {
		if ( ! is_string( $value ) ) {
			return 0;
		}

		$value = trim( $value );
		if ( $value === '' || $value === 'select-feed' ) {
			return 0;
		}

		if ( ! str_starts_with( $value, 'feed-' ) ) {
			return 0;
		}

		return absint( substr( $value, 5 ) );
	}

	private function buildFeedOptionKey( int $feedId ): string {
		return 'feed-' . $feedId;
	}

}
