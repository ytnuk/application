<?php

namespace WebEdit\Application;

use Nette\Application\UI;
use Nette\Utils;
use WebEdit\Application;

/**
 * @property-read Application\Presenter $presenter
 */
abstract class Control extends UI\Control
{

    private $view = 'View';
    private $functions = [];

    public function __call($name, $arguments = [])
    {
        if (Utils\Strings::startsWith($name, 'render')) {
            $default = $this->view;
            if ($name != 'render') {
                $this->view = Utils\Strings::substring($name, 6);
            }
            $result = call_user_func_array([$this, 'render'], $arguments);
            $this->view = $default;
            return $result;
        }
        return parent::__call($name, $arguments);
    }

    protected function createComponent($name)
    {
        return parent::createComponent($name) ?: $this->presenter->registerComponent($name);
    }

    private function render()
    {
        $this->callFunction('startup', NULL, TRUE);
        $this->callFunction('startup' . $this->view, func_get_args(), TRUE);
        $this->callFunction('beforeRender');
        $this->callFunction('render' . $this->view, func_get_args());
        $this->template->render($this['template'][lcfirst($this->view)]);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @param bool $once
     */
    private function callFunction($name, $arguments = NULL, $once = FALSE)
    {
        if ($once && isset($this->functions[$name])) {
            return;
        }
        if (method_exists($this, $name)) {
            call_user_func_array([$this, $name], $arguments ?: []);
        }
        if ($once) {
            $this->functions[$name] = TRUE;
        }
    }

}
