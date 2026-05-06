<?php

declare (strict_types=1);
namespace InaviiSocialFeedVendor\DI;

use InaviiSocialFeedVendor\Psr\Container\ContainerExceptionInterface;
/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
