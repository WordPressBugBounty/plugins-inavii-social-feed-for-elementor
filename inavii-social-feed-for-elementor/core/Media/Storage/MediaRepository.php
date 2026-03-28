<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Media\Storage;

use Inavii\Instagram\Media\Source\Storage\FeedSourcesRepository;
use Inavii\Instagram\Media\Source\Storage\SourcesRepository;

final class MediaRepository {
	private MediaPostsRepository $posts;
	private MediaFilesRepository $files;
	private SourcesRepository $sources;
	private FeedSourcesRepository $feedSources;

	public function __construct(
		MediaPostsRepository $posts,
		MediaFilesRepository $files,
		SourcesRepository $sources,
		FeedSourcesRepository $feedSources
	) {
		$this->posts       = $posts;
		$this->files       = $files;
		$this->sources     = $sources;
		$this->feedSources = $feedSources;
	}

	public function posts(): MediaPostsRepository {
		return $this->posts;
	}

	public function files(): MediaFilesRepository {
		return $this->files;
	}

	public function sources(): SourcesRepository {
		return $this->sources;
	}

	public function feedSources(): FeedSourcesRepository {
		return $this->feedSources;
	}
}
