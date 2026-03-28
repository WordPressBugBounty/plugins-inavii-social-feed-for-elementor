<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Domain\Policy;

final class ReconnectNoticePolicy {
	public function buildNotice( int $reconnectCount, string $settingsUrl ): array {
		if ( $reconnectCount <= 0 ) {
			return [];
		}

		return [
			'title'   => 'Accounts require reconnect',
			'message' => sprintf( '%d account(s) need to be reconnected to keep feeds updating.', $reconnectCount ),
			'link'    => $settingsUrl,
		];
	}
}
