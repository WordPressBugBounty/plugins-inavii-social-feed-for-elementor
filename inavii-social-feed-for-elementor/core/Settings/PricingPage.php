<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Settings;

final class PricingPage {
	public static function url(): string {
		return add_query_arg(
			[
				'page' => 'inavii-instagram-settings-pricing&trial=true',
			],
			esc_url( admin_url( 'admin.php' ) )
		);
	}
}
