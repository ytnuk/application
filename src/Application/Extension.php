<?php

namespace WebEdit\Application;

use Nette;
use WebEdit;

/**
 * Class Extension
 *
 * @package WebEdit\Application
 */
final class Extension extends Nette\Bridges\ApplicationDI\ApplicationExtension implements WebEdit\Config\Provider
{

	const COMPONENT_TAG = 'application.component';

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			parent::class => [
				'errorPresenter' => NULL
			],
			Nette\Bridges\Framework\NetteExtension::class => [
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
			->setFactory(Presenter\Factory::class)
			->addSetup('setComponents', [$components]);
	}
}
