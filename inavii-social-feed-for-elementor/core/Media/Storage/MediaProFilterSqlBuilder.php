<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Storage;

interface MediaProFilterSqlBuilder {
	/**
	 * @param array<string,mixed> $filters
	 * @param array<int,mixed>    $params
	 */
	public function buildWhereSql( array $filters, array &$params ): string;
}
