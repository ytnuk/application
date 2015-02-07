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
		return parent::getComponent(str_replace('\\', NULL, lcfirst($name)), $need);
	}

	/**
	 * @return array
	 */
	public function getFilteredParameters()
	{
		$parameters = $this->request->getParameters();
		unset($parameters['action']);

		return $parameters;
	}

	/**
	 * @param $component
	 * @param Ytnuk\Link\Entity|string $destination
	 * @param array $args
	 * @param $mode
	 *
	 * @return string
	 * @throws Nette\Application\UI\InvalidLinkException
	 */
	protected function createRequest($component, $destination, array $args, $mode)
	{
		if ($destination instanceof Ytnuk\Link\Entity) {
			$component = $this;
			$args = $destination->parameters->get()
				->fetchPairs('key');
			$destination = $destination->destination;
		}

		return parent::createRequest($component, $destination, $args, $mode);
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->snippetMode = $this->isAjax();
		if ($this->snippetMode && ! $this->request->isMethod('POST') && ! $this->getParameter('do')) {
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
		$component = parent::createComponent($name);
		if ( ! $component && isset($this->components[$name])) {
			$component = $this->context->getByType($this->components[$name]);
			if (method_exists($component, 'create')) {
				$component = call_user_func([
					$component,
					'create'
				]);
			}
		}

		return $component;
	}
}
