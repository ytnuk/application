<?php

namespace WebEdit\Application;

use Nette\Application;
use Nette\Utils\Strings;
use WebEdit;

abstract class Control extends Application\UI\Control {

    protected $view = 'view';

    public function __construct() {
        
    }

    protected function render() {
        $this->template->render($this->getTemplateFiles($this->view));
    }

    protected function getTemplateFiles($name) {
        $templates = [];
        $reflection = new WebEdit\Reflection($this);
        $local = 'C:\Users\vitkutny\Desktop\sandbox/local';
        do {
            $localTemplate = $local . '/' . $reflection->getModuleName($reflection->getShortName() . '/' . $name . '.latte', '/', FALSE);
            $path = pathinfo($reflection->getFileName());
            $template = $path['dirname'] . '/' . $path['filename'] . '/' . $name . '.latte';
            if (file_exists($localTemplate)) {
                $templates[] = $localTemplate;
            } elseif (file_exists($template)) {
                $templates[] = $template;
            }
        } while ($reflection = $reflection->getParentClass());
        return array_shift($templates);
    }

    public function __call($func, $args = []) {
        if (Strings::startsWith($func, 'render')) {
            $default = $this->view;
            if ($func !== 'render') {
                $this->view = Strings::lower(Strings::substring($func, 6));
            }
            $result = call_user_func_array([$this, 'render'], $args);
            $this->view = $default;
            return $result;
        }
        return parent::__call($func, $args);
    }

}
