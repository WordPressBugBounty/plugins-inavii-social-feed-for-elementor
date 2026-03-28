<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Includes\Legacy;

use Inavii\Instagram\Includes\Legacy\Assets\LegacyAdminAssetsLoader;
use Inavii\Instagram\Includes\Legacy\Assets\LegacyFrontAssetsLoader;
use Inavii\Instagram\Includes\Legacy\Integration\LegacyIntegrationRuntime;
use Inavii\Instagram\Includes\Legacy\Integration\WidgetsManager;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyDataMigrator;
use Inavii\Instagram\Includes\Legacy\Migration\LegacyMigrationQueue;
use Inavii\Instagram\Includes\Legacy\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Includes\Legacy\PostTypes\Media\MediaPostType;
use Inavii\Instagram\Wp\PostType;
use function Inavii\Instagram\Di\container;

final class Bootstrap {
	private LegacyAdminAssetsLoader $adminAssets;
	private LegacyFrontAssetsLoader $frontAssets;
	private LegacyMigrationQueue $migrationQueue;
	private LegacyIntegrationRuntime $integrationRuntime;
	private LegacyDataMigrator $migrator;

	public function __construct() {
		$this->adminAssets        = new LegacyAdminAssetsLoader();
		$this->frontAssets        = new LegacyFrontAssetsLoader();
		$this->integrationRuntime = new LegacyIntegrationRuntime();
		$this->migrationQueue     = container()->get( LegacyMigrationQueue::class );
		$this->migrator           = container()->get( LegacyDataMigrator::class );
	}

	public function init(): void {
		$this->integrationRuntime->register();
		add_action( 'init', [ $this, 'registerLegacyPostTypes' ] );
		add_action( 'init', [ $this, 'scheduleLegacyMigration' ], 20 );
		$this->adminAssets->init();
		$this->frontAssets->init();
		new WidgetsManager();
	}

	public function registerLegacyPostTypes(): void {
		PostType::register( new AccountPostType() );
		PostType::register( new MediaPostType() );
	}

	public function scheduleLegacyMigration(): void {
		if ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
			$this->migrator->maybeRunCritical();
			return;
		}

		$this->migrationQueue->maybeScheduleFull();
	}
}
