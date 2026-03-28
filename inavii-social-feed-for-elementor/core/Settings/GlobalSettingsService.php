<?php
declare(strict_types=1);

namespace Inavii\Instagram\Settings;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;
use Inavii\Instagram\Freemius\FreemiusAccess;
use Inavii\Instagram\Wp\AppGlobalSettings;

final class GlobalSettingsService {
	private AppGlobalSettings $settings;
	private ProFeaturesPolicy $proFeatures;

	public function __construct( AppGlobalSettings $settings, ProFeaturesPolicy $proFeatures ) {
		$this->settings    = $settings;
		$this->proFeatures = $proFeatures;
	}

	public function forApi(): array {
		$plans = $this->resolvePlans();

		$global = [
			'plans'                 => $plans,
			'numberOfPostsToImport' => $this->settings->getNumberOfPostsImported(),
			'renderOption'          => $this->settings->getRenderOption(),
		];

		return [
			'globalSettings' => $global,
			'plans'          => $plans,
			'timeZone'       => wp_timezone_string(),
			'pricingUrl'     => PricingPage::url(),
			'capabilities'   => $this->proFeatures->capabilitiesForApi(),
			'uiVersion'      => Plugin::uiVersion(),
		];
	}

	public function update( array $payload ): void {
		$renderOption = isset( $payload['renderOption'] ) ? (string) $payload['renderOption'] : '';
		$renderOption = strtoupper( trim( $renderOption ) );

		if ( $renderOption === 'AJAX' || $renderOption === 'PHP' ) {
			$this->settings->saveRenderOption( $renderOption );
		}

		$limit = $this->resolveImportLimit( $payload );
		if ( $limit !== null ) {
			$this->settings->saveNumberOfPostsImported( $limit );
		}
	}

	private function resolveImportLimit( array $payload ): ?int {
		if ( isset( $payload['numberOfPostsToImport'] ) && is_numeric( $payload['numberOfPostsToImport'] ) ) {
			return max( 1, (int) $payload['numberOfPostsToImport'] );
		}

		if (
			isset( $payload['globalSettings'] ) &&
			is_array( $payload['globalSettings'] ) &&
			isset( $payload['globalSettings']['numberOfPostsToImport'] ) &&
			is_numeric( $payload['globalSettings']['numberOfPostsToImport'] )
		) {
			return max( 1, (int) $payload['globalSettings']['numberOfPostsToImport'] );
		}

		return null;
	}

	private function resolvePlans(): array {
		$version = FreemiusAccess::version();
		$allowed = FreemiusAccess::canUsePremiumCode();

		return [
			'isEssentialsPlan' => $version->is_plan( 'essentials' ) && $allowed,
			'isProPlan'        => $version->is_plan( 'premium' ) && $allowed,
			'isUnlimitedPlan'  => $version->is_plan( 'unlimited' ) && $allowed,
		];
	}
}
