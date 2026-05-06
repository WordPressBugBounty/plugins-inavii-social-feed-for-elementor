<?php
declare(strict_types=1);

namespace Inavii\Instagram\Di;

use Inavii\Instagram\Freemius\FreemiusAccess;

final class ContainerConfigurator {
	private const PRO_MODULE_CLASS = '\\Inavii\\Instagram\\Pro\\Di\\ProModule';

	/** @var object|null */
	private static $container;

	/**
	 * @return object
	 */
	public static function container() {
		if ( is_object( self::$container ) ) {
			return self::$container;
		}

		$builderClass = DiVendorResolver::containerBuilderClass();
		$builder      = new $builderClass();
		$builder->useAutowiring( true );
		$builder->useAnnotations( false );

		// FREE definitions.
		$builder->addDefinitions( FreeModule::definitions() );

		$isPro = FreemiusAccess::canUsePremiumCode();

		if ( $isPro && class_exists( self::PRO_MODULE_CLASS ) ) {
			$proModuleClass = self::PRO_MODULE_CLASS;
			/** @var array $defs */
			$defs = $proModuleClass::definitions();
			$builder->addDefinitions( $defs );
		}

		self::$container = $builder->build();

		return self::$container;
	}
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed
if ( ! \function_exists( __NAMESPACE__ . '\\container' ) ) {
	/**
	 * @return object
	 */
	function container() {
		return ContainerConfigurator::container();
	}
}
// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed
