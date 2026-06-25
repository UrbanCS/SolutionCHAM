<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Extension\InstructorBillingComponent;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->registerServiceProvider(new MVCFactory('\\Cham\\Component\\InstructorBilling'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Cham\\Component\\InstructorBilling'));
		$container->registerServiceProvider(new RouterFactory('\\Cham\\Component\\InstructorBilling'));

		$container->set(
			ComponentInterface::class,
			static function (Container $container): ComponentInterface {
				$component = new InstructorBillingComponent(
					$container->get(ComponentDispatcherFactoryInterface::class)
				);

				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
