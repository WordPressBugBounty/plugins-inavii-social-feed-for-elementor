<?php
declare(strict_types=1);

namespace Inavii\Instagram\Feed\Application;

class MediaOrderRuntime {
	public function reorderForDisplay( array $posts, int $feedId, int $limit, int $offset ): array {
		return $posts;
	}
}
