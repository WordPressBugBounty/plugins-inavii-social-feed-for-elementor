<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Files;

final class MediaDownloader {

	/**
	 * @return string|\WP_Error
	 */
	public function downloadToTemp( string $url, int $timeout = 60 ) {
		if ( ! \function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return \download_url( $url, $timeout );
	}
}
