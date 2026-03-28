<?php
declare(strict_types=1);

namespace Inavii\Instagram\Di;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Inavii\Instagram\Freemius\FreemiusAccess;

final class ContainerConfigurator {
	private const PRO_MODULE_CLASS = '\\Inavii\\Instagram\\Pro\\Di\\ProModule';

	/** @var ContainerInterface|null */
	private static $container;

	public static function container(): ContainerInterface {
		if ( self::$container instanceof ContainerInterface ) {
			return self::$container;
		}

		$builder = new ContainerBuilder();
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
	function container(): ContainerInterface {
		return ContainerConfigurator::container();
	}
}
// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed
