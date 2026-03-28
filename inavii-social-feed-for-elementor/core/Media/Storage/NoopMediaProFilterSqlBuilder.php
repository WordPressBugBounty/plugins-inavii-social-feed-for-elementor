<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Storage;

final class NoopMediaProFilterSqlBuilder implements MediaProFilterSqlBuilder {
	public function buildWhereSql( array $filters, array &$params ): string {
		return '';
	}
}
