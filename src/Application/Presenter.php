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
	 * @var array
	 */
	private $components;

	/**
	 * @return Ytnuk\Templating\Template
	 */
	public function formatTemplateFiles()
	{
		return $this[Ytnuk\Templating\Template::class][$this->view];
	}

	/**
	 * @return Ytnuk\Templating\Template
	 */
	public function formatLayoutTemplateFiles()
	{
		return $this[Ytnuk\Templating\Template::class]['layout'];
	}

	/**
	 * @param array $components
	 */
	public function setComponents(array $components)
	{
		$this->components = $components;
	}

	/**
	 * @param $name
	 * @param bool $need
	 *
	 * @return Nette\ComponentModel\IComponent|NULL
	 */
	public function getComponent($name, $need = TRUE)
	{
		$name = $this->formatComponentName($name);

		return parent::getComponent($name, $need);
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function formatComponentName($name)
	{
		return str_replace('\\', NULL, lcfirst($name));
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		if ($this->snippetMode = $this->isAjax() && ! $this->request->isMethod('POST') && ! $this->getParameter('do')) {
			$this->redrawControl();
		}
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
	 * @param array $arguments
	 *
	 * @return Control
	 */
	protected function registerComponent($name, $arguments = [])
	{
		$name = $this->formatComponentName($name);
		$component = NULL;
		if (isset($this->components[$name])) {
			$component = $this->context->getByType($this->components[$name]);
			if (method_exists($component, 'create')) {
				$component = call_user_func_array([
					$component,
					'create'
				], $arguments);
			}
		}

		return $component;
	}
}
