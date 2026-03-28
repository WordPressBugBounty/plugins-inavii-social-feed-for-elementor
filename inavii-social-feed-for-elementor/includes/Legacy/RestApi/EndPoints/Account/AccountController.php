<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\RestApi\EndPoints\Account;

use Inavii\Instagram\Account\Application\AccountService;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\InstagramApi\InstagramApiException;
use Inavii\Instagram\Media\Application\MediaFetchService;
use Inavii\Instagram\Media\Source\Application\SyncAccountSources;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Source\Domain\SourceAccountPolicy;
use Inavii\Instagram\Wp\ApiResponse;
use Inavii\Instagram\Wp\AppGlobalSettings;
use WP_REST_Request;
use WP_REST_Response;

class AccountController {

	private AccountService $accountService;
	private AccountRepository $accountRepository;
	private MediaFetchService $mediaFetch;
	private SyncAccountSources $syncAccountSources;
	private SourceAccountPolicy $accountPolicy;
	private ApiResponse $api;

	public function __construct(
		AccountService $accountService,
		AccountRepository $accountRepository,
		MediaFetchService $mediaFetch,
		SyncAccountSources $syncAccountSources,
		SourceAccountPolicy $accountPolicy,
		ApiResponse $api
	) {
		$this->api                = $api;
		$this->accountService     = $accountService;
		$this->accountRepository  = $accountRepository;
		$this->mediaFetch         = $mediaFetch;
		$this->syncAccountSources = $syncAccountSources;
		$this->accountPolicy      = $accountPolicy;
	}

	public function connectAccount( WP_REST_Request $request ): WP_REST_Response {
		[$accessToken, $tokenExpires, $businessId] = $this->extractConnectParams( $request );

		if ( $accessToken === '' ) {
			return new WP_REST_Response( [ 'error' => 'Invalid params' ], 400 );
		}

		return $this->handleConnect( $accessToken, $tokenExpires, $businessId );
	}

	private function handleConnect( string $accessToken, int $tokenExpires, string $businessId ): WP_REST_Response {
		try {
			$account = $this->accountService->connect( $accessToken, $tokenExpires, $businessId );

			$username = $account->username();
			$name     = $username !== '' ? $username : $account->name();

			return $this->api->response(
				[
					'wpAccountID' => $account->id(),
					'name'        => $name,
				]
			);
		} catch ( InstagramApiException $e ) {
			return new WP_REST_Response(
				[
					'error'   => 'OAuth error',
					'message' => $e->getMessage(),
				],
				401
			);
		} catch ( \InvalidArgumentException $e ) {
			return new WP_REST_Response( [ 'error' => $e->getMessage() ], 400 );
		} catch ( \Throwable $e ) {
			return new WP_REST_Response(
				[
					'error'   => 'Unexpected error',
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * @return array{0:string,1:int,2:string}
	 */
	private function extractConnectParams( WP_REST_Request $request ): array {
		$data = $request->get_param( 'data' );

		$accessToken  = is_array( $data ) ? ( $data['accessToken'] ?? null ) : $request->get_param( 'accessToken' );
		$tokenExpires = is_array( $data ) ? ( $data['tokenExpires'] ?? null ) : $request->get_param( 'tokenExpires' );
		$businessId   = is_array( $data ) ? ( $data['userID'] ?? null ) : $request->get_param( 'businessId' );
		if ( $businessId === null && is_array( $data ) && isset( $data['businessId'] ) ) {
			$businessId = $data['businessId'];
		}

		$accessToken  = is_string( $accessToken ) ? sanitize_text_field( wp_unslash( $accessToken ) ) : '';
		$tokenExpires = is_numeric( $tokenExpires ) ? absint( $tokenExpires ) : 0;
		$businessId   = is_string( $businessId ) ? sanitize_text_field( wp_unslash( $businessId ) ) : '';

		return [ $accessToken, $tokenExpires, $businessId ];
	}

	public function all(): WP_REST_Response {
		try {
			$accounts = $this->accountService->api()->getAll();
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		}

		$accounts = array_map(
			function ( array $account ): array {
				$wpAccountId = isset( $account['id'] ) ? (int) $account['id'] : 0;
				$username    = isset( $account['username'] ) ? (string) $account['username'] : '';
				$avatar      = isset( $account['avatar'] ) ? (string) $account['avatar'] : '';
				$biography   = isset( $account['biography'] ) ? (string) $account['biography'] : '';
				$id          = isset( $account['igAccountId'] ) ? (string) $account['igAccountId'] : (string) $wpAccountId;
				$reconnect   = ! empty( $account['reconnectRequired'] );
				$sourceError = isset( $account['sourceError'] ) ? (string) $account['sourceError'] : '';

				return [
					'id'                   => $id,
					'wpAccountID'          => $wpAccountId,
					'accountType'          => isset( $account['accountType'] ) ? (string) $account['accountType'] : '',
					'connectType'          => isset( $account['connectType'] ) ? (string) $account['connectType'] : '',
					'name'                 => isset( $account['name'] ) ? (string) $account['name'] : '',
					'username'             => $username,
					'instagramProfileLink' => $username !== '' ? 'https://www.instagram.com/' . $username : '',
					'mediaCount'           => isset( $account['mediaCount'] ) ? (int) $account['mediaCount'] : 0,
					'tokenExpires'         => isset( $account['tokenExpires'] ) ? (int) $account['tokenExpires'] : 0,
					'avatar'               => $avatar,
					'avatarOverwritten'    => '',
					'biography'            => $biography,
					'biographyOverwritten' => '',
					'lastUpdate'           => $this->normalizeLegacyDateTime(
						isset( $account['lastUpdate'] ) ? (string) $account['lastUpdate'] : ''
					),
					'issues'               => [
						'count'             => $reconnect ? 1 : 0,
						'error'             => $sourceError,
						'reconnectRequired' => $reconnect,
					],
				];
			},
			$accounts
		);

		return $this->api->response( $accounts );
	}

	private function normalizeLegacyDateTime( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		try {
			$date = new \DateTimeImmutable( $value, wp_timezone() );
			return $date->format( DATE_ATOM );
		} catch ( \Throwable $e ) {
			return $value;
		}
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );
		if ( $id <= 0 ) {
			return $this->api->response( [], false, 'Invalid account id.' );
		}

		$avatar = $request->get_param( 'avatarOverwritten' );
		$avatar = is_string( $avatar ) ? esc_url_raw( wp_unslash( $avatar ) ) : '';

		try {
			$account = $this->accountService->get( $id );
			$account->updateAvatar( $avatar );
			$this->accountService->update( $account );

			return $this->api->response( true );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		}
	}

	public function updateBio( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request->get_param( 'id' ) );
		if ( $id <= 0 ) {
			return $this->api->response( [], false, 'Invalid account id.' );
		}

		$bio = $request->get_param( 'biographyOverwritten' );
		$bio = is_string( $bio ) ? sanitize_textarea_field( wp_unslash( $bio ) ) : '';

		try {
			$account = $this->accountService->get( $id );
			$account->updateProfile( $account->name(), $account->username(), $bio );
			$this->accountService->update( $account );

			return $this->api->response( true );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		}
	}

	public function delete( WP_REST_Request $request ): WP_REST_Response {
		$accountId = absint( $request->get_param( 'id' ) );
		if ( $accountId <= 0 ) {
			return $this->api->response( [], false, 'Invalid account id.' );
		}

		try {
			$this->accountService->delete( $accountId );
			return $this->api->response( [] );
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		}
	}

	public function reconnect( WP_REST_Request $request ): WP_REST_Response {
		[ $accessToken, $tokenExpires, $businessId ] = $this->extractConnectParams( $request );

		// When reconnect payload contains OAuth data, reuse the same connect flow as v2.
		if ( $accessToken !== '' ) {
			$response = $this->handleConnect( $accessToken, $tokenExpires, $businessId );
			if ( $response->get_status() >= 400 ) {
				return $response;
			}
		}

		do_action( AppGlobalSettings::CRON_SCHEDULE_UPDATE_MEDIA_TASK );

		return isset( $response ) ? $response : $this->api->response( 'true' );
	}

	public function cron( WP_REST_Request $request ): WP_REST_Response {
		$params    = $request->get_params();
		$accountId = isset( $params['accountId'] ) ? absint( $params['accountId'] ) : 0;
		$source    = isset( $params['source'] ) && is_array( $params['source'] ) ? $params['source'] : [];

		try {
			if ( $source !== [] ) {
				$this->syncLegacySources( $source );
			} elseif ( $accountId > 0 ) {
				$this->mediaFetch->fetch( Source::account( $accountId ) );
				$this->syncAccountSources->handle( $accountId );
			} else {
				throw new \InvalidArgumentException( 'Invalid source payload.' );
			}
		} catch ( \Throwable $e ) {
			return $this->api->response( [], false, $e->getMessage() );
		}

		return $this->api->response( [] );
	}

	/**
	 * @param array $source Legacy source payload from React v2.
	 */
	private function syncLegacySources( array $source ): void {
		$accountIds = $this->sanitizeIdList( $source['accounts'] ?? [] );
		$taggedIds  = $this->sanitizeIdList( $source['tagged'] ?? [] );
		$hashtags   = $this->sanitizeHashtags( $source['hashtags'] ?? [] );

		foreach ( $accountIds as $accountId ) {
			$this->mediaFetch->fetch( Source::account( $accountId ) );
		}

		foreach ( $taggedIds as $accountId ) {
			$account = $this->accountRepository->get( $accountId );
			if ( ! $this->accountPolicy->canUseForTaggedSource( $account ) ) {
				continue;
			}

			$this->mediaFetch->fetch(
				Source::tagged(
					$this->accountPolicy->igAccountId( $account ),
					$account->id()
				)
			);
		}

		if ( $hashtags !== [] ) {
			$businessAccountId = $this->resolveBusinessAccountId( array_merge( $accountIds, $taggedIds ) );
			foreach ( $hashtags as $tag ) {
				$this->mediaFetch->fetch( Source::hashtag( $tag, $businessAccountId ) );
			}
		}
	}

	/**
	 * @param mixed $value
	 *
	 * @return int[]
	 */
	private function sanitizeIdList( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$ids = array_map( 'absint', $value );
		$ids = array_filter(
			$ids,
			static function ( int $id ): bool {
				return $id > 0;
			}
		);

		return array_values( array_unique( $ids ) );
	}

	/**
	 * @param mixed $value
	 *
	 * @return string[]
	 */
	private function sanitizeHashtags( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$tags = [];
		foreach ( $value as $item ) {
			$tag = '';
			if ( is_array( $item ) ) {
				if ( isset( $item['id'] ) && is_scalar( $item['id'] ) ) {
					$tag = (string) $item['id'];
				}
			} elseif ( is_scalar( $item ) ) {
				$tag = (string) $item;
			}

			$tag = strtolower( ltrim( trim( $tag ), '#' ) );
			if ( $tag !== '' ) {
				$tags[] = $tag;
			}
		}

		return array_values( array_unique( $tags ) );
	}

	/**
	 * @param int[] $candidateIds
	 */
	private function resolveBusinessAccountId( array $candidateIds ): int {
		foreach ( $candidateIds as $candidateId ) {
			if ( $candidateId <= 0 ) {
				continue;
			}

			try {
				$account = $this->accountRepository->get( $candidateId );
			} catch ( \Throwable $e ) {
				continue;
			}

			if ( $this->accountPolicy->canUseForTaggedSource( $account ) ) {
				return $account->id();
			}
		}

		$fallback = $this->accountRepository->findBusinessAccount();
		if ( $fallback && $this->accountPolicy->canUseForTaggedSource( $fallback ) ) {
			return $fallback->id();
		}

		return 0;
	}
}
