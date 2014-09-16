<?php

namespace WebEdit\Application;

use Nette\Bridges;
use Nette\PhpGenerator;
use WebEdit\Application;
use WebEdit\Module;

final class Extension extends Module\Extension
{

    protected $resources = [
        'presenter' => [
            'mapping' => ['*' => 'WebEdit\*\*'],
            'components' => []
        ],
        'services' => []
    ];

    public function getApplicationResources()
    {
        return [
            'services' => [
                'application' => [
                    'class' => Application::class
                ],
                [
                    'class' => Application\Presenter\Factory::class,
                    'arguments' => [new PhpGenerator\PhpLiteral('$this')],
                    'setup' => [
                        'setMapping' => [$this->resources['presenter']['mapping']],
                        'setComponents' => [$this->resources['presenter']['components']]
                    ]
                ]
            ]
        ];
    }

    protected function startup()
    {
        $builder = $this->getContainerBuilder();
        $this->compiler->addExtension('cache', new Bridges\CacheDI\CacheExtension($builder->expand('%tempDir%')));
        $this->compiler->addExtension('nette', new Bridges\Framework\NetteExtension);
        $this->setupServices();
    }

    private function setupServices()
    {
        $builder = $this->getContainerBuilder();
        foreach ($this->resources['services'] + $this->resources['presenter']['components'] as $name => $service) {
            $name = is_string($name) ? $name : $this->prefix('service.' . $name);
            $service = is_array($service) ? $service : ['class' => $service];
            $definition = $builder->hasDefinition($name) ? $builder->getDefinition($name) : $builder->addDefinition($name);
            interface_exists($service['class']) ? $definition->setImplement($service['class']) : $definition->setClass($service['class']);
            if (isset($service['parameters'])) {
                $definition->setParameters($service['parameters']);
                if (!isset($service['arguments'])) {
                    $service['arguments'] = array_map(function ($parameter) {
                        return new PhpGenerator\PhpLiteral('$' . $parameter);
                    }, $service['parameters']);
                }
            }
            if (isset($service['arguments'])) {
                $definition->setArguments($service['arguments']);
            }
            if (!isset($service['setup'])) {
                continue;
            }
            foreach ($service['setup'] as $method => $arguments) {
                $definition->addSetup($method, $arguments);
            }
        }
    }

}
