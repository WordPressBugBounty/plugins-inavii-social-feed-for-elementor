<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Domain\Policy;

final class AccountSourceStatusPolicy {
	private TokenErrorClassifier $errors;

	public function __construct( TokenErrorClassifier $errors ) {
		$this->errors = $errors;
	}

	/**
	 * @param string     $accountLastUpdate
	 * @param array|null $source
	 *
	 * @return array
	 */
	public function resolve( string $accountLastUpdate, ?array $source ): array {
		$lastUpdate = trim( $accountLastUpdate );
		$lastError  = '';

		if ( is_array( $source ) ) {
			$lastError  = isset( $source['last_error'] ) ? trim( (string) $source['last_error'] ) : '';
			$lastUpdate = $this->resolveLastUpdate( $lastUpdate, $source );
		}

		$reconnectRequired = $lastError !== '' && $this->errors->isTokenErrorMessage( $lastError );

		return [
			'lastUpdate'        => $lastUpdate,
			'reconnectRequired' => $reconnectRequired,
			'sourceError'       => $lastError,
		];
	}

	private function resolveLastUpdate( string $current, array $source ): string {
		$fields = [ 'last_success_at', 'last_sync_at' ];

		foreach ( $fields as $field ) {
			$field = trim( (string) $field );
			if ( $field === '' ) {
				continue;
			}

			$value = isset( $source[ $field ] ) ? trim( (string) $source[ $field ] ) : '';
			if ( $value !== '' ) {
				return $value;
			}
		}

		return trim( $current );
	}
}
