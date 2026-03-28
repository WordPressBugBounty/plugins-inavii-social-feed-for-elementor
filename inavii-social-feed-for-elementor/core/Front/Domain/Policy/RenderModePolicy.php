<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Front\Domain\Policy;

final class RenderModePolicy {
	public function shouldUseAjaxRuntime( string $renderOption, bool $isElementorEditor, bool $isWordPressEditor ): bool {
		if ( strtoupper( $renderOption ) !== 'AJAX' ) {
			return false;
		}

		return ! $isElementorEditor && ! $isWordPressEditor;
	}
}
