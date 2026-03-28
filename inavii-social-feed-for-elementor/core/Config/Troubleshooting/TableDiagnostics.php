<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Config\Troubleshooting;

use Inavii\Instagram\Database\DatabaseStatusStore;
use Inavii\Instagram\Database\Tables\AccountsTable;
use Inavii\Instagram\Database\Tables\FeedFrontCacheTable;
use Inavii\Instagram\Database\Tables\FeedSourcesTable;
use Inavii\Instagram\Database\Tables\LogsTable;
use Inavii\Instagram\Database\Tables\MediaChildrenTable;
use Inavii\Instagram\Database\Tables\MediaTable;
use Inavii\Instagram\Database\Tables\SourcesTable;

final class TableDiagnostics {
	private AccountsTable $accounts;
	private MediaTable $media;
	private MediaChildrenTable $mediaChildren;
	private SourcesTable $sources;
	private FeedSourcesTable $feedSources;
	private FeedFrontCacheTable $feedFrontCache;
	private LogsTable $logs;
	private DatabaseStatusStore $statusStore;

	public function __construct(
		AccountsTable $accounts,
		MediaTable $media,
		MediaChildrenTable $mediaChildren,
		SourcesTable $sources,
		FeedSourcesTable $feedSources,
		FeedFrontCacheTable $feedFrontCache,
		LogsTable $logs,
		DatabaseStatusStore $statusStore
	) {
		$this->accounts       = $accounts;
		$this->media          = $media;
		$this->mediaChildren  = $mediaChildren;
		$this->sources        = $sources;
		$this->feedSources    = $feedSources;
		$this->feedFrontCache = $feedFrontCache;
		$this->logs           = $logs;
		$this->statusStore    = $statusStore;
	}

	/**
	 * @return array
	 */
	public function status(): array {
		$statusState = $this->statusStore->state();
		$tablesState = isset( $statusState['tables'] ) && is_array( $statusState['tables'] )
			? $statusState['tables']
			: [];

		return [
			$this->row( 'accounts', $this->accounts, $tablesState ),
			$this->row( 'media', $this->media, $tablesState ),
			$this->row( 'media_children', $this->mediaChildren, $tablesState ),
			$this->row( 'sources', $this->sources, $tablesState ),
			$this->row( 'feed_sources', $this->feedSources, $tablesState ),
			$this->row( 'feed_front_cache', $this->feedFrontCache, $tablesState ),
			$this->row( 'logs', $this->logs, $tablesState ),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function row( string $label, object $table, array $tablesState ): array {
		/** @var \Inavii\Instagram\Database\Tables\AbstractTable $table */
		$key         = method_exists( $table, 'statusKey' ) ? (string) $table->statusKey() : $label;
		$statusRow   = isset( $tablesState[ $key ] ) && is_array( $tablesState[ $key ] )
			? $tablesState[ $key ]
			: [];

		return [
			'label'  => $label,
			'table'  => $table->table_name(),
			'exists' => $table->exists(),
			'status' => isset( $statusRow['status'] ) ? (string) $statusRow['status'] : '',
			'last_error' => isset( $statusRow['last_error'] ) ? (string) $statusRow['last_error'] : '',
			'last_query' => isset( $statusRow['last_query'] ) ? (string) $statusRow['last_query'] : '',
			'updated_at' => isset( $statusRow['updated_at'] ) ? (string) $statusRow['updated_at'] : '',
		];
	}
}
