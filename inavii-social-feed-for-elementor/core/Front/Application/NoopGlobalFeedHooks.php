<?php
declare(strict_types=1);

namespace Inavii\Instagram\Front\Application;

use Inavii\Instagram\Front\Application\Contracts\GlobalFeedHooksRuntime;

final class NoopGlobalFeedHooks implements GlobalFeedHooksRuntime {
	public function register(): void {
	}
}
