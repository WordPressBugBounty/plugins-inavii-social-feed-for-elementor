<?php

function inavii_social_feed_e_fs_uninstall_cleanup() {
    function delete_all_custom_posts($post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any'
        ));

        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }

    function delete_media_directory() {
        $upload_dir = wp_upload_dir();
        $media_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'inavii-social-feed';

        if (file_exists($media_dir)) {
            $files = glob($media_dir . DIRECTORY_SEPARATOR . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($media_dir);
        }
    }

    $custom_post_types = ['inavii_ig_media', 'inavii_account', 'inavii_feed'];
    foreach ($custom_post_types as $post_type) {
        delete_all_custom_posts($post_type);
    }

    delete_media_directory();

    wp_clear_scheduled_hook('inavii_social_feed_update_media');
    wp_clear_scheduled_hook('inavii_social_feed_refresh_token');

    delete_option('inavii_social_feed_e_version');
}

?>
