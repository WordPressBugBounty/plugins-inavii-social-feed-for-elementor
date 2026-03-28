<?php
declare( strict_types=1 );

namespace Inavii\Instagram\FrontIndex\Domain\Policy;

final class AccountSelectionPolicy {
	/**
	 * @param array $sources
	 *
	 * @return int[]
	 */
	public function candidateAccountIds( array $sources ): array {
		$accountIds = [];

		foreach ( [ 'accounts', 'tagged' ] as $key ) {
			$items = isset( $sources[ $key ] ) && is_array( $sources[ $key ] ) ? $sources[ $key ] : [];
			foreach ( $items as $id ) {
				$id = (int) $id;
				if ( $id > 0 ) {
					$accountIds[] = $id;
				}
			}
		}

		$accountIds = array_values( array_unique( $accountIds ) );
		$primaryId  = isset( $sources['primaryAccountId'] ) ? (int) $sources['primaryAccountId'] : 0;
		if ( $primaryId <= 0 ) {
			return $accountIds;
		}

		if ( ! in_array( $primaryId, $accountIds, true ) ) {
			return $accountIds;
		}

		$ordered = [ $primaryId ];
		foreach ( $accountIds as $accountId ) {
			if ( $accountId === $primaryId ) {
				continue;
			}

			$ordered[] = $accountId;
		}

		return $ordered;
	}
}
