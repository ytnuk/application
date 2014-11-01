<?php

namespace WebEdit\Application;

use Nette\Application\UI;
use Nette\Utils;
use WebEdit\Application;

/**
 * Class Control
 *
 * @package WebEdit\Application
 */
abstract class Control extends UI\Control
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
	 * @var array
	 */
	private $counter = [];

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments = [])
	{
		if (Utils\Strings::startsWith($name, 'render')) {
			$default = $this->view;
			if ($name != 'render') {
				$this->view = lcfirst(Utils\Strings::substring($name, 6));
			}
			$result = call_user_func_array([
				$this,
				'render'
			], $arguments);
			$this->view = $default;

			return $result;
		}

		return parent::__call($name, $arguments);
	}

	/**
	 * @param string $name
	 * @return self
	 */
	protected function createComponent($name)
	{
		return parent::createComponent($name) ? : $this->presenter->registerComponent($name);
	}

	private function render()
	{
		if (isset($this->counter[$this->view])) {
			$this->counter[$this->view] += 1;
		} else {
			$this->counter[$this->view] = 1;
		}
		$this->template->uniqueId = $this->getUniqueId() . '-' . $this->view . '-' . $this->counter[$this->view]; //TODO: implement as helper |prefix
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
