<?php

namespace Ytnuk\Application;

use Nette;
use Ytnuk;

/**
 * Class Control
 *
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
	 * @var bool
	 */
	private $invalid = FALSE;

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments = [])
	{
		if (Nette\Utils\Strings::startsWith($name, $this->render)) {
			if ($this->presenter->isAjax() && ! $this->invalid) { //TODO: maybe invalid as array allowing redrawing of only some templates
				Nette\Bridges\ApplicationLatte\UIMacros::renderSnippets($this, new \stdClass, []);

				return NULL;
			}
			$name = lcfirst(Nette\Utils\Strings::substring($name, strlen($this->render))) ? : $this->view;
			$default = $this->view;
			$defaultSnippetMode = $this->snippetMode;
			unset($this->rendered[$name]);
			foreach (array_diff_key($this->getViews(), $this->rendered) as $view => $snippetMode) {
				$this->view = $view;
				if ($view === $name) {
					$this->snippetMode = $defaultSnippetMode && ! $snippetMode;
				} else {
					$this->snippetMode = ! $snippetMode;
				}
				ob_start();
				call_user_func_array([
					$this,
					$this->render
				], $arguments);
				$output = ob_get_clean();
				if ($snippetMode && $snippetId = $this->getSnippetId()) {
					if ( ! $this->snippetMode) {
						$output = '<div id="' . $snippetId . '">' . $output . '</div>';
					}
					if ($this->getPresenter()->isAjax()) {
						$this->getPresenter()->getPayload()->snippets[$snippetId] = $output;
					}
				}
				$this->rendered[$view] = $output;
			}
			$this->view = $default;
			if ( ! $this->snippetMode = $defaultSnippetMode) {
				echo $this->rendered[$name];
			}

			return $this->rendered[$name];
		}

		return parent::__call($name, $arguments);
	}

	/**
	 * @return array
	 */
	protected function getViews()
	{
		return [
			$this->view => TRUE,
		];
	}

	/**
	 * @param string|NULL $name
	 *
	 * @return string
	 */
	public function getSnippetId($name = NULL)
	{
		$id = parent::getSnippetId($name);
		$uniqueId = $this->getUniqueId();

		return str_replace($uniqueId, implode('-', [
			$uniqueId,
			$this->view
		]), $id);
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

	public function redrawControl($snippet = NULL, $redraw = TRUE)
	{
		$this->invalid = TRUE;
		parent::redrawControl($snippet, $redraw);
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
		$this->getTemplate()->render($this[Ytnuk\Templating\Template::class][$this->view]);
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
}
