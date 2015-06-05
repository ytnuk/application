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
	 * @var bool
	 */
	public $rendering = FALSE;

	/**
	 * @var array
	 */
	private $related = [];

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
	 * @var Nette\Caching\Cache
	 */
	private $cache;

	/**
	 * @inheritdoc
	 */
	public function __call($name, $arguments = [])
	{
		if (Nette\Utils\Strings::startsWith($name, $this->render)) {
			$this->rendering = TRUE;
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
				if ($this->cache && is_callable($snippetMode)) {
					$dependencies = [
						Nette\Caching\Cache::TAGS => [],
						Nette\Caching\Cache::FILES => []
					];
					$key = [
						$this->view,
						array_intersect_key($this->getPresenter()->getParameters(), array_flip($this->getPresenter()->getPersistentParams())),
					];
					$providers = [];
					foreach (call_user_func_array($snippetMode, [& $dependencies]) as $dependency) {
						if ($dependency instanceof Ytnuk\Cache\Provider) {
							$providers[] = $dependency;
							$key[] = $dependency->getCacheKey();
						} else {
							$key[] = $dependency;
						}
					}
					list($output, $this->related) = $this->cache->load($key, function (& $dp) use (&$dependencies, $providers) {
						foreach ($providers as $provider) {
							$dependencies[Nette\Caching\Cache::TAGS] = array_merge($dependencies[Nette\Caching\Cache::TAGS], $provider->getCacheTags());
						}
						$dependencies[Nette\Caching\Cache::TAGS][] = $this->cache->getNamespace();
						$dependencies[Nette\Caching\Cache::TAGS][] = $this->getUniqueId();
						$dependencies[Nette\Caching\Cache::TAGS][] = $this->getSnippetId();
						$output = $this->render();
						$dependencies[Nette\Caching\Cache::FILES][] = $this->getTemplate()->getFile();
						$dp = $dependencies;

						return [
							$output,
							$this->related,
						];
					});
				} else {
					$output = $this->render();
				}
				if ($snippetMode && $isAjax) {
					$payload->snippets[$this->getSnippetId()] = $output;
				}
				if ($control = $this->lookupRendering()) {
					$control->setRelated($this, $this->view);
				}
				$this->rendered[$view] = $output;
			}
			$this->view = $defaultView;
			$output = NULL;
			if ($this->snippetMode = $defaultSnippetMode) {
				Nette\Bridges\ApplicationLatte\UIMacros::renderSnippets($this, new \stdClass, []);
			} elseif (isset($this->rendered[$this->view = $name])) {
				$output = $this->rendered[$this->view];
				if ( ! $isAjax) {
					foreach ($this->related[$this->view] as $relatedName => $relatedViews) {
						foreach ($relatedViews as $relatedView => $relatedSnippetId) {
							$relatedSnippet = Nette\Utils\Html::el('div', ['id' => $relatedSnippetId]);
							if (strpos($output, (string) $relatedSnippet) !== FALSE) {
								$output = str_replace((string) $relatedSnippet, $relatedSnippet->setHtml(call_user_func([
									$this->getComponent($relatedName),
									$this->render . ucfirst($relatedView)
								], TRUE)), $output);
							}
						}
					}
				}
				if ( ! $arguments) {
					if ($this->views[$this->view] && $snippetId = $this->getSnippetId()) {
						$snippet = Nette\Utils\Html::el('div', ['id' => $snippetId]);
						if ($isAjax && $this->related[$this->view]) {
							foreach ($this->related[$this->view] as $subName => $subViews) {
								$this[$subName]->redrawControl();
							}
							$snippetMode = $this->snippetMode;
							Nette\Bridges\ApplicationLatte\UIMacros::renderSnippets($this, new \stdClass, []);
							$this->snippetMode = $snippetMode;
						}
						if (($isAjax && ! isset($payload->snippets[$snippetId])) || ( ! $isAjax && ! $this->lookupRendering())) {
							$snippet->setHtml($output);
						}
						$output = $snippet->render();
					}
					echo $output;
				}
				$this->view = $defaultView;
			}
			$this->rendering = FALSE;

			return $output;
		}

		return parent::__call($name, $arguments);
	}

	/**
	 * @inheritdoc
	 */
	public function getSnippetId($name = NULL)
	{
		$uniqueId = $this->getUniqueId();

		return str_replace($uniqueId, implode('-', [
			$uniqueId,
			isset($this->views[$name]) ? $name : $this->view
		]), parent::getSnippetId(isset($this->views[$name]) ? NULL : $name));
	}

	/**
	 * @return string
	 */
	private function render()
	{
		$this->cycle('startup', TRUE);
		$this->cycle('before' . ucfirst($this->render));
		$this->cycle($this->render . ucfirst($this->view));
		$this->getTemplate()->setFile($this[Ytnuk\Templating\Template\Factory::class][$this->view]);

		return (string) $this->getTemplate();
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
	 * @return self|NULL
	 */
	public function lookupRendering()
	{
		$control = $this;
		while ($control = $control->lookup(self::class, FALSE)) {
			if ($control->rendering) {
				return $control;
			}
		}

		return NULL;
	}

	/**
	 * @param Control $control
	 * @param string $view
	 */
	public function setRelated(self $control, $view)
	{
		$this->related[$this->view][substr($control->getUniqueId(), strlen($this->getUniqueId()) + 1)][$view] = $control->getSnippetId();
	}

	/**
	 * @inheritdoc
	 */
	public function getComponent($name, $need = TRUE)
	{
		return parent::getComponent(str_replace('\\', NULL, lcfirst($name)), $need);
	}

	/**
	 * @inheritdoc
	 */
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

	/**
	 * @param Nette\Caching\IStorage $storage
	 */
	public function setCacheStorage(Nette\Caching\IStorage $storage)
	{
		$this->cache = new Nette\Caching\Cache($storage, $this->getReflection()->getName());
	}

	/**
	 * @inheritdoc
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);
		$this->views = $this->getViews();
		$this->related = array_fill_keys(array_keys($this->views), []);
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
	 * @inheritdoc
	 */
	protected function createComponent($name)
	{
		return parent::createComponent($name) ? : $this->getPresenter()->createComponent($name);
	}
}
