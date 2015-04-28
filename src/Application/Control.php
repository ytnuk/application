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
	 * @var array
	 */
	private $invalidViews = [];

	/**
	 * @var array
	 */
	private $views = [];

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments = [])
	{
		if (Nette\Utils\Strings::startsWith($name, $this->render)) {
			$name = lcfirst(Nette\Utils\Strings::substring($name, strlen($this->render))) ? : $this->view;
			$isAjax = $this->getPresenter()->isAjax();
			$payload = $this->getPresenter()->getPayload();
			$defaultView = $this->view;
			$defaultSnippetMode = $this->snippetMode;
			$views = $this->views;
			if ($this->snippetMode) {
				$views = array_intersect_key($views, $this->invalidViews);
			} elseif ( ! $isAjax) {
				$views = array_intersect_key($views, [$name => TRUE]);
			}
			foreach (array_diff_key($views, $this->rendered) as $view => $snippetMode) {
				$this->view = $view;
				$this->snippetMode = $isAjax && ! $snippetMode;
				ob_start();
				$this->render();
				$output = ob_get_clean();
				if ($snippetMode && $isAjax && $snippetId = $this->getSnippetId()) {
					$payload->snippets[$snippetId] = $output;
				}
				$this->rendered[$view] = $output;
			}
			$this->view = $defaultView;
			if ($this->snippetMode = $defaultSnippetMode) {
				Nette\Bridges\ApplicationLatte\UIMacros::renderSnippets($this, new \stdClass, []);
			} elseif (isset($this->rendered[$this->view = $name])) {
				$output = $this->rendered[$this->view];
				if ($this->views[$this->view] && $snippetId = $this->getSnippetId()) {
					$snippet = Nette\Utils\Html::el('div', ['id' => $snippetId]);
					if ( ! isset($payload->snippets[$snippetId])) {
						$snippet->setHtml($output);
					}
					$output = $snippet->render();
				}
				echo $output;
			}
			$this->view = $defaultView;

			return NULL;
		}

		return parent::__call($name, $arguments);
	}

	private function render()
	{
		$this->cycle('startup', TRUE);
		$this->cycle('before' . ucfirst($this->render));
		$this->cycle($this->render . ucfirst($this->view));
		$this->getTemplate()->render($this[Ytnuk\Templating\Template::class][$this->view]);
	}

	/**
	 * @param string $method
	 * @param bool $once
	 */
	private function cycle($method, $once = FALSE)
	{
		if ($once && isset($this->cycle[$method])) {
			return;
		}
		if (method_exists($this, $method)) {
			call_user_func([
				$this,
				$method
			]);
		}
		if ($once) {
			$this->cycle[$method] = TRUE;
		}
	}

	/**
	 * @param string|NULL $name
	 *
	 * @return string
	 */
	public function getSnippetId($name = NULL)
	{
		$uniqueId = $this->getUniqueId();

		return str_replace($uniqueId, implode('-', [
			$uniqueId,
			$this->view
		]), parent::getSnippetId($name));
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
		if ($redraw) {
			if (isset($this->views[$snippet])) {
				$this->invalidViews[$snippet] = TRUE;
			} elseif ($snippet === NULL) {
				$this->invalidViews = $this->views;
			}
		} elseif ($snippet === NULL) {
			$this->invalidViews = [];
		} else {
			unset($this->invalidViews[$snippet]);
		}
		parent::redrawControl($snippet, $redraw);
	}

	protected function attached($presenter)
	{
		parent::attached($presenter);
		$this->views = $this->getViews();
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
	 * @param string $name
	 *
	 * @return self
	 */
	protected function createComponent($name)
	{
		return parent::createComponent($name) ? : $this->getPresenter()->createComponent($name);
	}
}
