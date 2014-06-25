<?php

namespace WebEdit\Application;

use Nette\Application;
use WebEdit\Menu;
use WebEdit;

abstract class Presenter extends Application\UI\Presenter {

    /**
     * @persistent
     */
    public $locale;
    private $menuControl;
    private $layouts;

    public function injectMenuControl(Menu\Control\Factory $control) {
        $this->menuControl = $control;
    }

    protected function createComponentMenu() {
        return $this->menuControl->create();
    }

    public function formatTemplateFiles() {
        return $this->getTemplateFiles($this->view);
    }

    public function formatLayoutTemplateFiles() {
        if (!$this->layouts) {
            $this->layouts = $this->getTemplateFiles('@layout');
        } else {
            array_shift($this->layouts);
        }

        return $this->layouts;
    }

    protected function getTemplateFiles($name) { //TODO: same as in Application\Control -> service
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
        return $templates;
    }

}
