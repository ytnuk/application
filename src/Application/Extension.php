<?php

namespace WebEdit\Application;

use Nette\Bridges;
use WebEdit\Application;
use WebEdit\Config;

/**
 * Class Extension
 *
 * @package WebEdit\Application
 */
final class Extension extends Bridges\ApplicationDI\ApplicationExtension implements Config\Provider
{

	const COMPONENT_TAG = 'application.component';

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			parent::class => [
				'mapping' => [
					'*' => 'WebEdit\*\*'
				],
				'errorPresenter' => NULL
			],
			Bridges\Framework\NetteExtension::class => [
				'session' => [
					'debugger' => TRUE
				],
				'container' => [
					'debugger' => TRUE
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
			->setFactory(Application\Presenter\Factory::class)
			->addSetup('setComponents', [$components]);
	}
}
