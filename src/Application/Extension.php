<?php

namespace Ytnuk\Application;

use Nette;
use Ytnuk;

/**
 * Class Extension
 *
 * @package Ytnuk\Application
 */
final class Extension extends Nette\DI\CompilerExtension implements Ytnuk\Config\Provider
{

	const COMPONENT_TAG = 'application.component';

	/**
	 * @inheritdoc
	 */
	public function getConfigResources()
	{
		return [
			Nette\Bridges\ApplicationDI\ApplicationExtension::class => [
				'errorPresenter' => NULL,
				'mapping' => [
					'*' => 'Ytnuk\*\*'
				],
				'scanDirs' => [
					'%appDir%/../vendor'
				]
			],
			Nette\Bridges\HttpDI\SessionExtension::class => [
				'debugger' => TRUE,
			],
			Nette\DI\Extensions\DIExtension::class => [
				'debugger' => TRUE,
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$components = [];
		foreach ($builder->findByTag(self::COMPONENT_TAG) as $name => $component) {
			$definition = $builder->getDefinition($name);
			$components[str_replace('_', NULL, lcfirst($name))] = $definition->getImplement() ? : $definition->getClass();
		}
		$presenterFactory = $builder->getDefinition('application.presenterFactory');
		$presenterFactory->getFactory()->setEntity(Presenter\Factory::class);
		$presenterFactory->addSetup('setComponents', [$components]);
	}
}
