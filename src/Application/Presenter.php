<?php

namespace WebEdit\Application;

use Nette\Application;
use WebEdit\Templating;

/**
 * Class Presenter
 *
 * @package WebEdit\Application
 */
abstract class Presenter extends Application\UI\Presenter
{

	/**
	 * @persistent
	 */
	public $locale;
	/**
	 * @var array
	 */
	private $components;

	/**
	 * @return Templating\Template
	 */
	public function formatTemplateFiles()
	{
		return $this['template'][$this->view];
	}

	/**
	 * @return Templating\Template
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
	 * @return Control
	 */
	protected function createComponent($name)
	{
		return parent::createComponent($name) ? : $this->registerComponent($name);
	}

	/**
	 * @param string $name
	 * @return Control
	 */
	public function registerComponent($name)
	{
		$component = NULL;
		if (isset($this->components[$name])) {
			$class = isset($this->components[$name]['class']) ? $this->components[$name]['class'] : $this->components[$name];
			$component = $this->context->getByType($class)
				->create();
		}

		return $component;
	}
}
