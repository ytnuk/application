<?php

namespace WebEdit\Application;

use Nette\DI;

abstract class Extension extends DI\CompilerExtension {

    public function getTranslationResources() {
        return [dirname(dirname(dirname($this->reflection->getFileName()))) . '/locale'];
    }

}
