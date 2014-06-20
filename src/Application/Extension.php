<?php

namespace WebEdit\Application;

use Nette\DI;
use WebEdit\Translation;

abstract class Extension extends DI\CompilerExtension implements Translation\Provider {

    public function getTranslationResources() {
        return [dirname(dirname(dirname($this->reflection->getFileName()))) . '/locale'];
    }

}
