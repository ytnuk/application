<?php

namespace WebEdit\Application;

use Nette;

/**
 * Class Control
 *
 * @package WebEdit\Application
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
	private $functions = [];

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments = [])
	{
		if (Nette\Utils\Strings::startsWith($name, 'render')) {
			$view = $this->view;
			if ($name != 'render') {
				$this->view = lcfirst(Nette\Utils\Strings::substring($name, 6));
			}
			$result = call_user_func_array([
				$this,
				'render'
			], $arguments);
			$this->view = $view;

			return $result;
		}

		return parent::__call($name, $arguments);
	}

	/**
	 * @param string $name
	 *
	 * @return self
	 */
	protected function createComponent($name)
	{
		return parent::createComponent($name) ? : $this->presenter->registerComponent($name);
	}

	private function render()
	{
		$this->callFunction('startup', [], TRUE);
		$this->callFunction('startup' . ucfirst($this->view), func_get_args(), TRUE);
		$this->callFunction('beforeRender');
		$this->callFunction('render' . ucfirst($this->view), func_get_args());
		$this->template->render($this['template'][$this->view]);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @param bool $once
	 */
	private function callFunction($name, array $arguments = [], $once = FALSE)
	{
		if ($once && isset($this->functions[$name])) {
			return;
		}
		if (method_exists($this, $name)) {
			call_user_func_array([
				$this,
				$name
			], $arguments);
		}
		if ($once) {
			$this->functions[$name] = TRUE;
		}
	}
}
