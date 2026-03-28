<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Integration\Editor;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Feed\Domain\Feed;
use Inavii\Instagram\Feed\Storage\FeedRepository;
use Inavii\Instagram\Front\Render;

use function Inavii\Instagram\Di\container;

final class InaviiFeedBlock {
	private const BLOCK_NAME    = 'inavii/social-feed';
	private const SCRIPT_HANDLE = 'inavii-social-feed-editor-block';
	private const SCRIPT_PATH   = 'includes/Integration/Editor/assets/feed-block.js';

	public function init(): void {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueEditorPreviewAssets' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueueEditorPreviewAssets' ] );
	}

	public function register(): void {
		if ( Plugin::isLegacyUi() ) {
			return;
		}

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$path = INAVII_INSTAGRAM_DIR . self::SCRIPT_PATH;
		if ( ! is_file( $path ) ) {
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			INAVII_INSTAGRAM_URL . self::SCRIPT_PATH,
			[
				'wp-i18n',
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
			],
			Plugin::version(),
			true
		);

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.InaviiEditorBlockConfig = ' . $this->safeJson( $this->editorConfig() ) . ';',
			'before'
		);

		register_block_type(
			self::BLOCK_NAME,
			[
				'api_version'     => 2,
				'editor_script'   => self::SCRIPT_HANDLE,
				'render_callback' => [ $this, 'render' ],
				'attributes'      => [
					'feedId' => [
						'type'    => 'number',
						'default' => 0,
					],
				],
				'supports'        => [
					'html' => false,
				],
			]
		);
	}

	public function render( array $attributes ): string {
		$feedId = isset( $attributes['feedId'] ) ? (int) $attributes['feedId'] : 0;
		if ( $feedId <= 0 ) {
			return $this->renderSelectNotice();
		}

		return do_shortcode( sprintf( '[inavii-feed id="%d"]', $feedId ) );
	}

	public function enqueueEditorPreviewAssets(): void {
		if ( Plugin::isLegacyUi() ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( function_exists( 'wp_should_load_block_editor_scripts_and_styles' ) ) {
			if ( ! wp_should_load_block_editor_scripts_and_styles() ) {
				return;
			}
		}

		do_action( 'inavii/social-feed/front/enqueue_editor_preview_assets' );
	}

	private function editorConfig(): array {
		return [
			'feeds'       => $this->feeds(),
			'settingsUrl' => admin_url( 'admin.php?page=inavii-instagram-settings' ),
		];
	}

	private function feeds(): array {
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

			$id = (int) $feed->id();
			if ( $id <= 0 ) {
				continue;
			}

			$title = trim( (string) $feed->title() );
			if ( $title === '' ) {
				$title = 'Feed #' . $id;
			}

			$post      = get_post( $id );
			$createdAt = '';
			if ( $post instanceof \WP_Post ) {
				$createdAt = (string) ( $post->post_date_gmt ?: $post->post_date );
			}

			$items[] = [
				'id'        => $id,
				'title'     => $title,
				'createdAt' => $createdAt,
			];
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

		return array_map(
			static function ( array $item ): array {
				return [
					'id'    => (int) $item['id'],
					'title' => (string) $item['title'],
				];
			},
			$items
		);
	}

	private function renderSelectNotice(): string {
		if ( ! is_admin() ) {
			return '';
		}

		try {
			/** @var Render $renderer */
			$renderer = container()->get( Render::class );
			return $renderer->renderEmptyState(
				esc_html__( 'Select a feed to preview', 'inavii-social-feed' ),
				esc_html__( 'Choose a feed in block settings to display it here.', 'inavii-social-feed' )
			);
		} catch ( \Throwable $e ) {
			return '<div class="inavii-block-empty">' . esc_html__( 'Select a feed in block settings.', 'inavii-social-feed' ) . '</div>';
		}
	}

	private function safeJson( array $data ): string {
		$json = wp_json_encode(
			$data,
			JSON_UNESCAPED_UNICODE
			| JSON_UNESCAPED_SLASHES
			| JSON_HEX_TAG
			| JSON_HEX_AMP
			| JSON_HEX_APOS
			| JSON_HEX_QUOT
		);

		return is_string( $json ) ? $json : '{}';
	}
}
