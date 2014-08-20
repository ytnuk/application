<?php

namespace WebEdit\Application;

use WebEdit\Bootstrap;
use WebEdit\Application;
use Nette\PhpGenerator;

final class Extension extends Bootstrap\Extension {

    private $resources = [
        'presenter' => [
            'mapping' => ['*' => 'WebEdit\*\*'],
            'components' => []
        ],
        'services' => []
    ];

    public function beforeCompile() {
        $this->loadResources();
        $this->setupServices();
        $this->setupPresenter();
    }

    private function loadResources() {
        $this->resources = $this->getConfig($this->resources);
        foreach ($this->compiler->getExtensions() as $extension) {
            if (!$extension instanceof Application\Provider) {
                continue;
            }
            $this->resources = array_merge_recursive($this->resources, $extension->getApplicationResources());
        }
    }

    private function setupServices() {
        $builder = $this->getContainerBuilder();
        foreach ($this->resources['services'] as $class => $parameters) {
            if (!is_string($class)) {
                $class = $parameters;
                $parameters = [];
            }
            $definition = $builder->addDefinition(lcfirst(stripslashes($class)));
            interface_exists($class) ? $definition->setImplement($class) : $definition->setClass($class);
            $definition->setParameters($parameters);
            $definition->setArguments(
                    array_map(function($parameter) {
                        return new PhpGenerator\PhpLiteral('$' . $parameter);
                    }, $parameters));
        }
    }

    private function setupPresenter() {
        $builder = $this->getContainerBuilder();
        $builder->getDefinition('nette.presenterFactory')
                ->addSetup('setMapping', [$this->resources['presenter']['mapping']]);
        foreach ($this->resources['presenter']['components'] as $name => $component) {
            $builder->addDefinition($this->prefix('presenter.component.' . $name))
                    ->setImplement($component);
        }
    }

}
