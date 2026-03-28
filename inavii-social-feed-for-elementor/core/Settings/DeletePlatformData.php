<?php
declare(strict_types=1);

namespace Inavii\Instagram\Settings;

use Inavii\Instagram\Database\Tables\AccountsTable;
use Inavii\Instagram\Database\Tables\FeedFrontCacheTable;
use Inavii\Instagram\Database\Tables\FeedSourcesTable;
use Inavii\Instagram\Database\Tables\LogsTable;
use Inavii\Instagram\Database\Tables\MediaChildrenTable;
use Inavii\Instagram\Database\Tables\MediaTable;
use Inavii\Instagram\Database\Tables\SourcesTable;
use Inavii\Instagram\Media\Application\MediaFileService;

final class DeletePlatformData {
	private AccountsTable $accounts;
	private MediaTable $media;
	private MediaChildrenTable $mediaChildren;
	private SourcesTable $sources;
	private FeedSourcesTable $feedSources;
	private FeedFrontCacheTable $frontCache;
	private LogsTable $logs;
	private MediaFileService $files;

	public function __construct(
		AccountsTable $accounts,
		MediaTable $media,
		MediaChildrenTable $mediaChildren,
		SourcesTable $sources,
		FeedSourcesTable $feedSources,
		FeedFrontCacheTable $frontCache,
		LogsTable $logs,
		MediaFileService $files
	) {
		$this->accounts      = $accounts;
		$this->media         = $media;
		$this->mediaChildren = $mediaChildren;
		$this->sources       = $sources;
		$this->feedSources   = $feedSources;
		$this->frontCache    = $frontCache;
		$this->logs          = $logs;
		$this->files         = $files;
	}

	public function handle(): void {
		$this->deleteLegacyPosts();
		$this->truncateTable( $this->feedSources );
		$this->truncateTable( $this->sources );
		$this->truncateTable( $this->mediaChildren );
		$this->truncateTable( $this->media );
		$this->truncateTable( $this->accounts );
		$this->truncateTable( $this->frontCache );
		$this->truncateTable( $this->logs );
		delete_option( 'inavii_social_feed_global_offcanvas_feed_id' );
		$this->files->deleteMediaDirectory();
	}

	private function deleteLegacyPosts(): void {
		$this->deletePostsByType( 'inavii_account' );
		$this->deletePostsByType( 'inavii_feed' );
		$this->deletePostsByType( 'inavii_ig_media' );
	}

	private function deletePostsByType( string $postType ): void {
		$posts = get_posts(
			[
				'post_type'   => $postType,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			]
		);

		if ( ! is_array( $posts ) || $posts === [] ) {
			return;
		}

		foreach ( $posts as $postId ) {
			$postId = (int) $postId;
			if ( $postId <= 0 ) {
				continue;
			}

			wp_delete_post( $postId, true );
		}
	}

	private function truncateTable( $table ): void {
		if ( ! method_exists( $table, 'exists' ) || ! $table->exists() ) {
			return;
		}

		global $wpdb;
		$name = (string) $table->table_name();
		if ( preg_match( '/^[A-Za-z0-9_]+$/', $name ) !== 1 ) {
			return;
		}

		$query = 'TRUNCATE TABLE `' . $name . '`';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $query );
	}
}
