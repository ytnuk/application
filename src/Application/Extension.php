<?php

namespace WebEdit\Application;

use WebEdit\DI;

final class Extension extends DI\Extension {

    private $defaults = [
        'mapping' => ['*' => 'WebEdit\*\*']
    ];

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);
        $builder->getDefinition('nette.presenterFactory')
                ->addSetup('setMapping', [$config['mapping']]);
    }

}
