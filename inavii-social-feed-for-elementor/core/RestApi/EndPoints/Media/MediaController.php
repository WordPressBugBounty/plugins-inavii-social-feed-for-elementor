<?php
declare( strict_types=1 );

namespace Inavii\Instagram\RestApi\EndPoints\Media;

use Inavii\Instagram\Media\Application\MediaFetchService;
use Inavii\Instagram\Media\Source\Application\SyncAccountSources;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

final class MediaController {
	private MediaFetchService $fetch;
	private SyncAccountSources $syncAccountSources;
	private ApiResponse $api;

	public function __construct( MediaFetchService $fetch, SyncAccountSources $syncAccountSources, ApiResponse $api ) {
		$this->fetch              = $fetch;
		$this->syncAccountSources = $syncAccountSources;
		$this->api                = $api;
	}

	public function import( WP_REST_Request $request ): WP_REST_Response {
		$kind = $request->get_param( 'kind' );
		$kind = is_string( $kind ) ? strtolower( trim( sanitize_text_field( wp_unslash( $kind ) ) ) ) : 'account';

		try {
			$source = $this->resolveSource( $request, $kind );
		} catch ( \InvalidArgumentException $e ) {
			return new WP_REST_Response( [ 'error' => $e->getMessage() ], 400 );
		}

		$syncRelated = $request->get_param( 'syncRelated' );
		$syncRelated = filter_var( $syncRelated, FILTER_VALIDATE_BOOLEAN );

		if ( $kind === 'account' && $syncRelated ) {
			try {
				$accountId    = $source->accountId();
				$sourcesTotal = $this->syncAccountSources->handle( $accountId );

				return $this->api->response(
					[
						'sourcesTotal' => $sourcesTotal,
						'sourcesOk'    => $sourcesTotal,
						'itemsFetched' => 0,
						'itemsSaved'   => 0,
						'errors'       => [],
					]
				);
			} catch ( \Throwable $e ) {
				return $this->unexpectedErrorResponse( $e );
			}
		}

		try {
			$result = $this->fetch->fetch( $source );

			return $this->api->response(
				[
					'sourcesTotal' => $result->sourcesTotal,
					'sourcesOk'    => $result->sourcesOk,
					'itemsFetched' => $result->itemsFetched,
					'itemsSaved'   => $result->itemsSaved,
					'errors'       => array_map(
						static function ( $error ): array {
							return $error->toArray();
						},
						$result->errors()
					),
				]
			);
		} catch ( \Throwable $e ) {
			return $this->unexpectedErrorResponse( $e );
		}
	}

	private function resolveSource( WP_REST_Request $request, string $kind ): Source {
		if ( $kind === 'account' || $kind === Source::KIND_ACCOUNT ) {
			$accountId = $request->get_param( 'accountId' );
			$accountId = is_numeric( $accountId ) ? absint( $accountId ) : 0;
			if ( $accountId <= 0 ) {
				throw new \InvalidArgumentException( 'Invalid accountId' );
			}

			return Source::account( $accountId );
		}

		if ( $kind === 'tagged' || $kind === Source::KIND_TAGGED ) {
			$username = $request->get_param( 'username' );
			$value    = is_string( $username ) ? $username : (string) $request->get_param( 'value' );
			$value    = sanitize_text_field( wp_unslash( $value ) );

			return Source::tagged( $value );
		}

		if ( $kind === 'hashtag' || $kind === Source::KIND_HASHTAG ) {
			$tag   = $request->get_param( 'tag' );
			$value = is_string( $tag ) ? $tag : (string) $request->get_param( 'value' );
			$value = sanitize_text_field( wp_unslash( $value ) );

			return Source::hashtag( $value );
		}

		throw new \InvalidArgumentException( 'Invalid kind' );
	}

	private function unexpectedErrorResponse( \Throwable $e ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'error'   => 'Unexpected error',
				'message' => $e->getMessage(),
			],
			500
		);
	}
}
