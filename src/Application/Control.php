<?php

namespace WebEdit\Application;

use Nette\Application\UI;
use Nette\Utils\Strings;
use WebEdit\Templating;
use WebEdit\Application;

abstract class Control extends UI\Control {

    private $view = 'View';
    private $functions = [];

    private function render() {
        $this->callFunction('startup', NULL, TRUE);
        $this->callFunction('startup' . $this->view, func_get_args(), TRUE);
        $this->callFunction('beforeRender');
        $this->callFunction('render' . $this->view, func_get_args());
        return $this->template->render($this['template'][lcfirst($this->view)]);
    }

    private function callFunction($name, $arguments = NULL, $once = FALSE) {
        if ($once && isset($this->functions[$name])) {
            return;
        }
        if (method_exists($this, $name)) {
            call_user_func_array([$this, $name], $arguments ? : []);
        }
        if ($once) {
            $this->functions[$name] = TRUE;
        }
    }

    protected function createComponentTemplate() {
        return new Application\Control\Multiplier(function($view) {
            return new Templating\Template($view);
        });
    }

    public function __call($name, $arguments = []) {
        if (Strings::startsWith($name, 'render')) {
            $default = $this->view;
            if ($name != 'render') {
                $this->view = Strings::substring($name, 6);
            }
            $result = call_user_func_array([$this, 'render'], $arguments);
            $this->view = $default;
            return $result;
        }
        return parent::__call($name, $arguments);
    }

}
