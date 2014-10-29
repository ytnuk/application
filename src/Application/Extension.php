<?php

namespace WebEdit\Application;

use Nette\Bridges;
use Nette\PhpGenerator;
use WebEdit\Application;
use WebEdit\Module;

final class Extension extends Module\Extension implements Application\Provider
{

	public function getApplicationResources()
	{
		return ['presenter' => ['mapping' => ['*' => 'WebEdit\*\*']]];
	}

	public function getResources()
	{
		return ['presenter' => ['components' => []], 'services' => [$this->prefix() => Application::class, $this->prefix('factory') => ['class' => Application\Presenter\Factory::class, 'arguments' => [new PhpGenerator\PhpLiteral('$this')], 'setup' => ['setMapping' => [$this['presenter']['mapping']], 'setComponents' => [$this['presenter']['components']]]]]];
	}

	public function loadConfiguration()
	{
		$this->setupServices();
	}

	private function setupServices()
	{
		$builder = $this->getContainerBuilder();
		foreach ($this['services'] + $this['presenter']['components'] as $name => $service) {
			$name = is_string($name) ? $name : $this->prefix('service.' . $name);
			$service = is_array($service) ? $service : ['class' => $service];
			$definition = $builder->hasDefinition($name) ? $builder->getDefinition($name) : $builder->addDefinition($name);
			interface_exists($service['class']) ? $definition->setImplement($service['class']) : $definition->setClass($service['class']);
			if (isset($service['implement'])) {
				$definition->setImplement($service['implement']);
			}
			if (isset($service['parameters'])) {
				$definition->setParameters($service['parameters']);
				if ( ! isset($service['arguments'])) {
					$service['arguments'] = array_map(function ($parameter) {
						return new PhpGenerator\PhpLiteral('$' . $parameter);
					}, $service['parameters']);
				}
			}
			if (isset($service['arguments'])) {
				$definition->setArguments($service['arguments']);
			}
			if ( ! isset($service['setup'])) {
				continue;
			}
			foreach ($service['setup'] as $method => $arguments) {
				$definition->addSetup($method, $arguments);
			}
		}
	}
}
