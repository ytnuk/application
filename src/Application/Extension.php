<?php
namespace Ytnuk\Application;

use Nette;
use Ytnuk;

final class Extension
	extends Nette\DI\CompilerExtension
	implements Ytnuk\Config\Provider
{

	const COMPONENT_TAG = 'application.component';

	public function getConfigResources() : array
	{
		return [
			Nette\Bridges\ApplicationDI\ApplicationExtension::class => [
				'errorPresenter' => NULL,
				'mapping' => [
					'*' => 'Ytnuk\*\*',
				],
				'scanDirs' => FALSE,
			],
			Nette\Bridges\HttpDI\SessionExtension::class => [
				'debugger' => TRUE,
			],
			Nette\DI\Extensions\DIExtension::class => [
				'debugger' => TRUE,
			],
			Nette\DI\Extensions\DecoratorExtension::class => [
				Control::class => [
					'setup' => [
						'setCacheStorage',
					],
				],
			],
		];
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$components = [];
		foreach (
			$builder->findByTag(self::COMPONENT_TAG) as $name => $component
		) {
			$definition = $builder->getDefinition($name);
			$components[str_replace(
				'\\',
				NULL,
				lcfirst($definition->getClass())
			)] = $definition->getImplement() ? : $definition->getClass();
		}
		$presenterFactory = $builder->getDefinition('application.presenterFactory');
		$presenterFactory->getFactory()->setEntity(Presenter\Factory::class);
		$presenterFactory->addSetup(
			'setComponents',
			[$components]
		);
	}
}
