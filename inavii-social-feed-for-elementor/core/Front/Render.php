<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front;

use Inavii\Instagram\Front\Application\EmptyStatePayloadFactory;
use Inavii\Instagram\Front\Application\UseCase\GetFrontMediaPage;
use Inavii\Instagram\Front\Application\UseCase\GetFrontPayload;
use Inavii\Instagram\Front\Domain\Policy\RenderModePolicy;
use Inavii\Instagram\Includes\Integration\Elementor\ElementorEditorContext;
use Inavii\Instagram\Includes\Integration\Editor\WordPressEditorContext;
use Inavii\Instagram\RestApi\PublicRequestKey;
use Inavii\Instagram\Wp\AppGlobalSettings;

final class Render {
	private PayloadRegistry $registry;
	private AppGlobalSettings $settings;
	private ElementorEditorContext $elementorContext;
	private WordPressEditorContext $wordPressEditorContext;
	private RenderModePolicy $renderMode;
	private GetFrontPayload $getPayload;
	private GetFrontMediaPage $getMediaPage;
	private EmptyStatePayloadFactory $emptyStatePayloads;
	private PublicRequestKey $publicKeys;

	private static bool $didEnqueue       = false;
	private static int $instance          = 0;
	private static array $renderedFeedIds = [];

	public function __construct(
		PayloadRegistry $registry,
		AppGlobalSettings $settings,
		ElementorEditorContext $elementorContext,
		WordPressEditorContext $wordPressEditorContext,
		RenderModePolicy $renderMode,
		GetFrontPayload $getPayload,
		GetFrontMediaPage $getMediaPage,
		EmptyStatePayloadFactory $emptyStatePayloads,
		PublicRequestKey $publicKeys
	) {
		$this->registry               = $registry;
		$this->settings               = $settings;
		$this->elementorContext       = $elementorContext;
		$this->wordPressEditorContext = $wordPressEditorContext;
		$this->renderMode             = $renderMode;
		$this->getPayload             = $getPayload;
		$this->getMediaPage           = $getMediaPage;
		$this->emptyStatePayloads     = $emptyStatePayloads;
		$this->publicKeys             = $publicKeys;
	}

	public function render( $feedId ): string {
		$feedId = (int) $feedId;
		if ( $feedId <= 0 ) {
			return '';
		}

		if ( $this->shouldUseAjaxRuntime() ) {
			self::$renderedFeedIds[ $feedId ] = true;
			$this->enqueueFrontAppOnce();

			return sprintf(
				'<div class="inavii-social-feed" data-inavii-social-feed data-feed-id="%s"%s data-inavii-render-mode="ajax"></div>',
				esc_attr( $feedId ),
				$this->feedPublicKeyAttribute( $feedId )
			);
		}

		try {
			$payload = $this->payload( $feedId, $this->previewPayloadLimit() );
		} catch ( \Throwable $e ) {
			return $this->renderInvalidFeedState( $feedId );
		}

		if ( $payload === [] ) {
			return $this->renderInvalidFeedState( $feedId );
		}

		return $this->renderInlinePayload( $feedId, $payload );
	}

	public function renderEmptyState( string $title, string $text, int $feedId = 0 ): string {
		return $this->renderInlinePayload(
			$feedId,
			$this->emptyStatePayloads->build( $feedId, $title, $text )
		);
	}

	public function renderInvalidFeedState( int $feedId ): string {
		return $this->renderInlinePayload( $feedId, $this->createBasePayload( $feedId ), true );
	}

	/**
	 * @param int   $feedId
	 * @param array $payload
	 * @param bool  $invalidFeed
	 *
	 * @return string
	 */
	private function renderInlinePayload( int $feedId, array $payload, bool $invalidFeed = false ): string {
		self::$renderedFeedIds[ $feedId ] = true;
		$this->enqueueFrontAppOnce();

		$key = 'feed' . $feedId . '_' . ( ++ self::$instance );
		$this->registry->add( $key, $payload );

		$inlinePayload = $this->safeJson( $payload );
		$invalidAttr   = $invalidFeed ? ' data-inavii-invalid-feed="1"' : '';

		return sprintf(
			'<div class="inavii-social-feed" data-inavii-social-feed data-feed-id="%s"%s data-inavii-key="%s"%s><script type="application/json" data-inavii-inline-payload>%s</script></div>',
			esc_attr( $feedId ),
			$this->feedPublicKeyAttribute( $feedId ),
			esc_attr( $key ),
			$invalidAttr,
			$inlinePayload
		);
	}

	/**
	 * @param int $feedId Feed ID.
	 * @param int $limit Media limit. Set to 0 to use default preload behavior.
	 * @param int $offset Media offset.
	 *
	 * @return array
	 */
	public function payload( int $feedId, int $limit = 0, int $offset = 0 ): array {
		return $this->getPayload->handle( $feedId, $limit, $offset );
	}

	/**
	 * @param int $feedId Feed ID.
	 * @param int $limit Media page size.
	 * @param int $offset Media offset.
	 *
	 * @return array
	 */
	public function media( int $feedId, int $limit = GetFrontMediaPage::DEFAULT_PAGE_SIZE, int $offset = 0 ): array {
		return $this->getMediaPage->handle( $feedId, $limit, $offset );
	}

	private function enqueueFrontAppOnce(): void {
		if ( self::$didEnqueue ) {
			return;
		}
		self::$didEnqueue = true;

		do_action( 'inavii/social-feed/front/enqueue_mode_aware_assets' );
	}

	public function hasRenderedFeed( int $feedId ): bool {
		if ( $feedId <= 0 ) {
			return false;
		}

		return isset( self::$renderedFeedIds[ $feedId ] );
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

	private function shouldUseAjaxRuntime(): bool {
		return $this->renderMode->shouldUseAjaxRuntime(
			$this->settings->getRenderOption(),
			$this->elementorContext->isEditorContext(),
			$this->wordPressEditorContext->isEditorContext()
		);
	}

	private function previewPayloadLimit(): int {
		if ( strtoupper( $this->settings->getRenderOption() ) !== 'AJAX' ) {
			return 0;
		}

		if ( ! $this->elementorContext->isEditorContext() && ! $this->wordPressEditorContext->isEditorContext() ) {
			return 0;
		}

		return GetFrontMediaPage::DEFAULT_PAGE_SIZE;
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

	private function feedPublicKeyAttribute( int $feedId ): string {
		if ( $feedId <= 0 ) {
			return '';
		}

		$key = $this->publicKeys->createFeedKey( $feedId );
		if ( $key === '' ) {
			return '';
		}

		return sprintf( ' data-inavii-feed-public-key="%s"', esc_attr( $key ) );
	}
}
