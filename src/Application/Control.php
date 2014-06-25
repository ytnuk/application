<?php

namespace WebEdit\Application;

use Nette\Application;
use Nette\Utils\Strings;
use WebEdit;

abstract class Control extends Application\UI\Control {

    protected $view = 'view';

    public function __construct() {
        parent::__construct(NULL, NULL);
    }

    protected function render() {
        $this->template->render($this->getTemplateFiles($this->view));
    }

    protected function getTemplateFiles($name) { //TODO: same as in Application\Presenter -> service
        $templates = [];
        $reflection = new WebEdit\Reflection($this);
        $local = '/home/vitkutny/Public/sandbox/private/src';
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

    public function __call($name, $arguments = []) {
        if (Strings::startsWith($name, 'render')) {
            $default = $this->view;
            if ($name !== 'render') {
                $this->view = lcfirst(Strings::substring($name, 6));
            }
            $result = call_user_func_array([$this, 'render'], $arguments);
            $this->view = $default;
            return $result;
        }
        return parent::__call($name, $arguments);
    }

}
