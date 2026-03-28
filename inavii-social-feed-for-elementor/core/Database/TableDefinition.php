<?php
declare(strict_types=1);

namespace Inavii\Instagram\Database;

/**
 * Contract for custom database tables managed by the plugin.
 */
interface TableDefinition {

	public function register_table(): void;
	public function maybe_create(): void;
}
