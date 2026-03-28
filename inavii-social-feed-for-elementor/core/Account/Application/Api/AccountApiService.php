<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Application\Api;

use Inavii\Instagram\Account\Domain\Account;
use Inavii\Instagram\Account\Domain\Policy\AccountSourceStatusPolicy;
use Inavii\Instagram\Account\Storage\AccountRepository;
use Inavii\Instagram\Media\Source\Storage\SourcesRepository;

final class AccountApiService {
	private AccountRepository $repository;
	private AccountPresenter $presenter;
	private SourcesRepository $sources;
	private AccountSourceStatusPolicy $status;

	public function __construct(
		AccountRepository $repository,
		AccountPresenter $presenter,
		SourcesRepository $sources,
		AccountSourceStatusPolicy $status
	) {
		$this->repository = $repository;
		$this->presenter  = $presenter;
		$this->sources    = $sources;
		$this->status     = $status;
	}

	/**
	 * @param Account $account
	 *
	 * @return array
	 */
	public function map( Account $account ): array {
		$source = $this->sources->findAccountSource( $account->id() );

		return $this->mapWithSource( $account, $source );
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function get( int $id ): array {
		$account = $this->repository->get( $id );
		$source  = $this->sources->findAccountSource( $account->id() );

		return $this->mapWithSource( $account, $source );
	}

	/**
	 * @return array
	 */
	public function getAll(): array {
		$accounts  = $this->repository->all();
		$sourceMap = $this->sources->getAccountSourcesByAccountIds(
			array_map(
				static function ( Account $account ): int {
					return $account->id();
				},
				$accounts
			)
		);

		return array_map(
			function ( Account $account ) use ( $sourceMap ): array {
				$source = $sourceMap[ $account->id() ] ?? null;

				return $this->mapWithSource( $account, $source );
			},
			$accounts
		);
	}

	/**
	 * @param Account    $account
	 * @param array|null $source
	 *
	 * @return array
	 */
	private function mapWithSource( Account $account, ?array $source ): array {
		$data   = $this->presenter->forApi( $account );
		$status = $this->status->resolve(
			isset( $data['lastUpdate'] ) ? (string) $data['lastUpdate'] : '',
			$source
		);

		$data['lastUpdate']        = $this->normalizeLastUpdate(
			isset( $status['lastUpdate'] ) ? (string) $status['lastUpdate'] : ''
		);
		$data['reconnectRequired'] = ! empty( $status['reconnectRequired'] );
		$data['sourceError']       = isset( $status['sourceError'] ) ? (string) $status['sourceError'] : '';

		return $data;
	}

	private function normalizeLastUpdate( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		try {
			if ( preg_match( '/(?:Z|[+\-]\d{2}:\d{2})$/i', $value ) === 1 ) {
				return ( new \DateTimeImmutable( $value ) )->format( DATE_ATOM );
			}

			$normalized = str_replace( ' ', 'T', $value );

			return ( new \DateTimeImmutable( $normalized, new \DateTimeZone( 'UTC' ) ) )
				->setTimezone( new \DateTimeZone( 'UTC' ) )
				->format( DATE_ATOM );
		} catch ( \Throwable $e ) {
			return $value;
		}
	}
}
