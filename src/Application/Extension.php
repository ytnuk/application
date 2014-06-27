<?php

namespace WebEdit\Application;

use WebEdit\Bootstrap;

final class Extension extends Bootstrap\Extension {

    private $defaults = [
        'presenter' => [
            'mapping' => ['*' => 'WebEdit\*\*']
        ]
    ];

    public function beforeCompile() {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);
        $builder->getDefinition('nette.presenterFactory')
                ->addSetup('setMapping', [$config['presenter']['mapping']]);
    }

}
