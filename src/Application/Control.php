<?php

namespace Ytnuk\Application;

use Nette;
use Ytnuk;

/**
 * Class Control
 *
 * @property-read Presenter $presenter
 * @package Ytnuk\Application
 */
abstract class Control extends Nette\Application\UI\Control
{

	/**
	 * @var string
	 */
	private $view = 'view';

	/**
	 * @var array
	 */
	private $cycle = [];

	/**
	 * @var array
	 */
	private $rendered = [];

	/**
	 * @var string
	 */
	private $render = 'render';

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments = [])
	{
		if (Nette\Utils\Strings::startsWith($name, $this->render)) {
			$views = [];
			if ($name === $this->render) {
				//TODO: is it possible to disable snippets including in nette?
				//TODO: or some way to check that this is invoked by snippets renderer and not from template
				$views += $this->getPresenter()->isAjax() ? array_filter(array_diff_key($this->getViews(), $this->rendered), function ($ajax) {
					return $ajax;
				}) : [
					$this->view => TRUE
				];
			} else {
				$views[lcfirst(Nette\Utils\Strings::substring($name, strlen($this->render)))] = TRUE;
			}
			$default = $this->view;
			foreach ($views as $view => $ajax) {
				$this->view = $view;
				$this->rendered[$view] = call_user_func_array([
					$this,
					$this->render
				], $arguments);
			}
			$this->view = $default;

			return $this->rendered;
		}

		return parent::__call($name, $arguments);
	}

	/**
	 * @return array
	 */
	protected function getViews()
	{
		return [
			$this->view => TRUE
		];
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
	 * @param string $name
	 *
	 * @return self
	 */
	protected function createComponent($name)
	{
		return parent::createComponent($name) ? : $this->getPresenter()->createComponent($name);
	}

	/**
	 * @return Ytnuk\Templating\Template
	 */
	private function render()
	{
		$this->cycle('startup', [], TRUE);
		$this->cycle('startup' . ucfirst($this->view), func_get_args(), TRUE);
		$this->cycle('beforeRender');
		$this->cycle($this->render . ucfirst($this->view), func_get_args());
		$this->getTemplate()->render($template = $this[Ytnuk\Templating\Template::class][$this->view]);

		return $template;
	}

	/**
	 * @param string $method
	 * @param array $arguments
	 * @param bool $once
	 */
	private function cycle($method, array $arguments = [], $once = FALSE)
	{
		if ($once && isset($this->cycle[$method])) {
			return;
		}
		if (method_exists($this, $method)) {
			call_user_func_array([
				$this,
				$method
			], $arguments);
		}
		if ($once) {
			$this->cycle[$method] = TRUE;
		}
	}

	/**
	 * @return Nette\Bridges\ApplicationLatte\Template
	 */
	public function getTemplate()
	{
		return parent::getTemplate();
	}
}
