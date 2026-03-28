<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function inavii_social_feed_e_fs_uninstall_cleanup() {
	if ( defined( 'INAVII_INSTAGRAM_DIR' ) ) {
		$file = INAVII_INSTAGRAM_DIR . '/core/Config/Uninstall.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	if ( class_exists( '\\Inavii\\Instagram\\Config\\Uninstall' ) ) {
		\Inavii\Instagram\Config\Uninstall::run();
		return;
	}

	delete_option( 'inavii_social_feed_e_version' );
}
