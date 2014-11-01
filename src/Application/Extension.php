<?php

namespace WebEdit\Application;

use Nette\Bridges;
use Nette\DI;
use WebEdit\Application;
use WebEdit\Config;

/**
 * Class Extension
 *
 * @package WebEdit\Application
 */
final class Extension extends DI\CompilerExtension implements Config\Provider
{

	const COMPONENT_TAG = 'application.component';

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			Bridges\ApplicationDI\ApplicationExtension::class => [
				'mapping' => [
					'*' => 'WebEdit\*\*'
				]
			],
			'services' => [
				'nette.presenterFactory' => [
					'factory' => Application\Presenter\Factory::class
				]
			]
		];
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$components = $builder->findByTag(self::COMPONENT_TAG);
		foreach ($components as $name => $component) {
			$definition = $builder->getDefinition($name);
			$components[$name] = $definition->getImplement() ? : $definition->getClass();
		}
		$builder->getDefinition('nette.presenterFactory')
			->addSetup('setComponents', [$components]);
	}
}
