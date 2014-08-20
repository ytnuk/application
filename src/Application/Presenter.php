<?php

namespace WebEdit\Application;

use Nette\Application\UI;
use WebEdit\Menu;
use WebEdit\Templating;
use WebEdit\Application;

abstract class Presenter extends UI\Presenter {

    /**
     * @persistent
     */
    public $locale;
    private $menuControl;

    public function injectMenuControl(Menu\Control\Factory $control) {
        $this->menuControl = $control;
    }

    protected function createComponentMenu() {
        return $this->menuControl->create();
    }

    public function formatTemplateFiles() {
        return $this['template'][$this->view];
    }

    public function formatLayoutTemplateFiles() {
        return $this['template']['layout'];
    }

    protected function createComponentTemplate() {
        return new Application\Control\Multiplier(function($view) {
            return new Templating\Template($view);
        });
    }

}
