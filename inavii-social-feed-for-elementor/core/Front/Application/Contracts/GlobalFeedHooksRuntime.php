<?php
declare(strict_types=1);

namespace Inavii\Instagram\Front\Application\Contracts;

interface GlobalFeedHooksRuntime {
	public function register(): void;
}
