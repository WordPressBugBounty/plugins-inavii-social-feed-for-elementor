<?php
declare(strict_types=1);

namespace Inavii\Instagram\Front\Application;

use Inavii\Instagram\Front\Application\Contracts\GlobalFeedRendererRuntime;

final class NoopGlobalFeedRenderer implements GlobalFeedRendererRuntime {
	public function register(): void {
	}
}
