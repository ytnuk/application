<?php

namespace Ytnuk\Application;

use Nette;
use Ytnuk;

/**
 * Class Extension
 *
 * @package Ytnuk\Application
 */
final class Extension extends Nette\Bridges\ApplicationDI\ApplicationExtension implements Ytnuk\Config\Provider
{

	const COMPONENT_TAG = 'application.component';

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			parent::class => [
				'errorPresenter' => NULL,
				'mapping' => [
					'*' => 'Ytnuk\*\*'
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

	public function beforeCompile()
	{
		parent::beforeCompile();
		$builder = $this->getContainerBuilder();
		$components = [];
		foreach ($builder->findByTag(self::COMPONENT_TAG) as $name => $component) {
			$definition = $builder->getDefinition($name);
			$components[str_replace('_', NULL, lcfirst($name))] = $definition->getImplement() ? : $definition->getClass();
		}
		$builder->getDefinition('nette.presenterFactory')
			->setFactory(Presenter\Factory::class)
			->addSetup('setComponents', [$components]);
	}
}
