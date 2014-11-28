<?php

namespace Ytnuk\Application;

use Nette;
use Ytnuk;

/**
 * Class Presenter
 *
 * @package Ytnuk\Application
 */
abstract class Presenter extends Nette\Application\UI\Presenter
{

	/**
	 * @var string
	 * @persistent
	 */
	public $locale;

	/**
	 * @var array
	 */
	private $components;

	/**
	 * @return Ytnuk\Templating\Template
	 */
	public function formatTemplateFiles()
	{
		return $this['template'][$this->view];
	}

	/**
	 * @return Ytnuk\Templating\Template
	 */
	public function formatLayoutTemplateFiles()
	{
		return $this['template']['layout'];
	}

	/**
	 * @param array $components
	 */
	public function setComponents(array $components)
	{
		$this->components = $components;
	}

	/**
	 * @param string $name
	 *
	 * @return Control
	 */
	protected function createComponent($name)
	{
		return parent::createComponent($name) ? : $this->registerComponent($name);
	}

	/**
	 * @param string $name
	 *
	 * @return Control
	 */
	public function registerComponent($name)
	{
		$component = NULL;
		if (isset($this->components[$name])) {
			$component = $this->context->getByType($this->components[$name]);
			if (method_exists($component, 'create')) {
				$component = $component->create();
			}
		}

		return $component;
	}
}
