<?php

namespace WebEdit\Application;

use Nette;

final class Configurator extends Nette\Configurator {

    protected function createContainerFactory() {
        unset($this->defaultExtensions['php']);
        unset($this->defaultExtensions['constants']);
        unset($this->defaultExtensions['database']);
        unset($this->defaultExtensions['mail']);
        foreach ($this->defaultExtensions as $name => $extension) {
            if (in_array($name, ['extensions'])) {
                continue;
            }
            $this->defaultExtensions[$name . '.nette'] = $extension;
            unset($this->defaultExtensions[$name]);
        }
        return parent::createContainerFactory();
    }

}
