<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Domain;

final class Feed {
	private int $id;
	private string $title;
	private string $feedType;
	private string $feedMode;
	private FeedSettings $settings;

	public function __construct( int $id, string $title, string $feedType, string $feedMode, FeedSettings $settings ) {
		if ( $id <= 0 ) {
			throw new \InvalidArgumentException( 'Feed id must be positive.' );
		}

		$title = trim( $title );
		if ( $title === '' ) {
			throw new \InvalidArgumentException( 'Feed title cannot be empty.' );
		}

		$this->id       = $id;
		$this->title    = $title;
		$this->feedType = trim( $feedType );
		$this->feedMode = trim( $feedMode );
		$this->settings = $settings;
	}

	public function id(): int {
		return $this->id;
	}

	public function title(): string {
		return $this->title;
	}

	public function feedType(): string {
		return $this->feedType;
	}

	public function feedMode(): string {
		return $this->feedMode;
	}

	public function settings(): FeedSettings {
		return $this->settings;
	}

	public function rename( string $title ): void {
		$title = trim( $title );
		if ( $title === '' ) {
			throw new \InvalidArgumentException( 'Feed title cannot be empty.' );
		}

		$this->title = $title;
	}

	public function replaceSettings( FeedSettings $settings ): void {
		$this->settings = $settings;
	}

	public function updateFeedMode( string $feedMode ): void {
		$feedMode = trim( $feedMode );
		if ( $feedMode === '' ) {
			throw new \InvalidArgumentException( 'Feed mode cannot be empty.' );
		}

		$this->feedMode = $feedMode;
	}
}
