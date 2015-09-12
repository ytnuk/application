<?php
namespace Ytnuk\Application;

use Nette;
use Ytnuk;

abstract class Control
	extends Nette\Application\UI\Control
	implements Ytnuk\Cache\Provider
{

	const RENDER_METHOD = 'render';

	/**
	 * @var bool
	 */
	private $rendering = FALSE;

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

	public function __call(
		$name,
		$arguments = []
	) {
		if (Nette\Utils\Strings::startsWith(
			$name,
			self::RENDER_METHOD
		)
		) {
			$arguments = current($arguments) ? : $arguments;
			$arguments = (array) $arguments + [
					'snippet' => TRUE,
					'echo' => TRUE,
				];
			$this->isRendering(TRUE);
			$name = lcfirst(
				Nette\Utils\Strings::substring(
					$name,
					strlen(self::RENDER_METHOD)
				)
			) ? : $this->view;
			$isAjax = $this->getPresenter()->isAjax();
			$payload = $this->getPresenter()->getPayload();
			$defaultView = $this->view;
			$defaultSnippetMode = $this->snippetMode;
			$views = $this->views;
			if ($this->snippetMode) {
				$views = array_intersect_key(
					$views,
					$this->invalidViews
				);
			} elseif ( ! $isAjax) {
				$views = array_intersect_key(
					$views,
					[$name => TRUE]
				);
			}
			foreach (
				array_diff_key(
					$views,
					$this->rendered
				) as $view => $snippetMode
			) {
				$this->view = $view;
				$this->snippetMode = $isAjax && ! $snippetMode;
				if ($this->cache && is_callable($snippetMode)) {
					$dependencies = [
						Nette\Caching\Cache::TAGS => [],
						Nette\Caching\Cache::FILES => [],
					];
					$key = $this->getCacheKey();
					$providers = [];
					foreach (
						$snippetMode(
							...
							[& $dependencies]
						) as $dependency
					) {
						if ($dependency instanceof Ytnuk\Cache\Provider) {
							$providers[] = $dependency;
							$key[] = $dependency->getCacheKey();
						} else {
							$key[] = $dependency;
						}
					}
					list($output, $this->related) = $this->cache->load(
						$key,
						function (& $dp) use
						(
							&$dependencies,
							$providers
						) {
							foreach (
								$providers as $provider
							) {
								if ($provider instanceof Ytnuk\Cache\Provider) {
									$dependencies[Nette\Caching\Cache::TAGS] = array_merge(
										$dependencies[Nette\Caching\Cache::TAGS],
										array_keys($provider->getCacheTags())
									);
								}
							}
							$dependencies[Nette\Caching\Cache::TAGS] = array_merge(
								$dependencies[Nette\Caching\Cache::TAGS],
								array_keys($this->getCacheTags())
							);
							$dependencies[Nette\Caching\Cache::TAGS][] = $this->cache->getNamespace();
							$output = $this->render();
							$dependencies[Nette\Caching\Cache::FILES][] = $this->getTemplate()->getFile();
							$dp = $dependencies;

							return [
								$output,
								$this->related,
							];
						}
					);
				} else {
					$output = $this->render();
				}
				if ($snippetMode && $isAjax) {
					$payload->snippets[$this->getSnippetId()] = $output;
				}
				if ($control = $this->lookupRendering()) {
					$control->setRelated(
						$this,
						$this->view
					);
				}
				$this->rendered[$view] = $output;
			}
			$this->view = $defaultView;
			$output = NULL;
			if ($this->snippetMode = $defaultSnippetMode) {
				Nette\Bridges\ApplicationLatte\UIRuntime::renderSnippets(
					$this,
					new \stdClass,
					[]
				);
			} elseif (isset($this->rendered[$this->view = $name])) {
				$output = $this->rendered[$this->view];
				if ( ! $isAjax) {
					foreach (
						$this->related[$this->view] as $relatedName => $relatedViews
					) {
						$related = $this->getComponent($relatedName);
						foreach (
							$relatedViews as $relatedView => $relatedSnippetId
						) {
							$relatedSnippet = Nette\Utils\Html::el(
								'div',
								['id' => $relatedSnippetId]
							);
							if (strpos(
									$output,
									(string) $relatedSnippet
								) !== FALSE
							) {
								$output = str_replace(
									(string) $relatedSnippet,
									$relatedSnippet->setHtml(
										call_user_func(
											[
												$related,
												self::RENDER_METHOD . ucfirst($relatedView),
											],
											[
												'echo' => FALSE,
												'snippet' => FALSE,
											]
										)
									),
									$output
								);
							}
						}
					}
				}
				if ($arguments['snippet'] && $this->views[$this->view] && $snippetId = $this->getSnippetId()) {
					$snippet = Nette\Utils\Html::el(
						'div',
						['id' => $snippetId]
					);
					if ($isAjax && $this->related[$this->view]) {
						foreach (
							$this->related[$this->view] as $subName => $subViews
						) {
							$related = isset($this[$subName]) ? $this[$subName] : NULL;
							if ($related instanceof Nette\Application\UI\IRenderable) {
								$related->redrawControl();
							}
						}
						$snippetMode = $this->snippetMode;
						Nette\Bridges\ApplicationLatte\UIRuntime::renderSnippets(
							$this,
							new \stdClass,
							[]
						);
						$this->snippetMode = $snippetMode;
					}
					if (($isAjax && ! isset($payload->snippets[$snippetId])) || ( ! $isAjax && ! $this->lookupRendering())) {
						$snippet->setHtml(
							$output
						);
					}
					$output = $snippet->render();
				}
				if ($arguments['echo']) {
					echo $output;
				}
				$this->view = $defaultView;
			}
			$this->isRendering(FALSE);

			return $output;
		}

		return parent::__call(
			$name,
			$arguments
		);
	}

	protected function attached($presenter)
	{
		parent::attached($presenter);
		$this->views = $this->getViews();
		$this->related = array_fill_keys(
			array_keys($this->views),
			[]
		);
	}

	protected function createComponent($name) : Nette\ComponentModel\IComponent
	{
		return parent::createComponent($name) ? : $this->getPresenter()->createComponent($name);
	}

	public function getComponent(
		$name,
		bool $need = TRUE
	) {
		return parent::getComponent(
			str_replace(
				'\\',
				NULL,
				lcfirst($name)
			),
			$need
		);
	}

	public function getSnippetId($name = NULL) : string
	{
		$uniqueId = $this->getUniqueId();

		return str_replace(
			$uniqueId,
			implode(
				'-',
				[
					$uniqueId,
					isset($this->views[$name]) ? $name : $this->view,
				]
			),
			parent::getSnippetId(isset($this->views[$name]) ? NULL : $name)
		);
	}

	public function redrawControl(
		string $snippet = NULL,
		bool $redraw = TRUE
	) {
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
		parent::redrawControl(
			$snippet,
			$redraw
		);
	}

	public function isRendering(bool $rendering = NULL) : bool
	{
		return $this->rendering = $rendering === NULL ? $this->rendering : $rendering;
	}

	public function getCacheKey() : array
	{
		return [
			$this->view,
			array_intersect_key(
				$this->getPresenter()->getParameters(),
				array_flip($this->getPresenter()->getPersistentParams())
			),
		];
	}

	public function getCacheTags() : array
	{
		return [
			$this->getUniqueId() => TRUE,
			$this->getSnippetId() => TRUE,
		];
	}

	public function lookupRendering()
	{
		$control = $this;
		do {
			if ($control !== $this && $control->isRendering()) {
				break;
			}
			$control = $control->lookup(
				self::class,
				FALSE
			);
		} while ($control instanceof self);

		return $control;
	}

	public function setRelated(
		self $control,
		string $view
	) {
		$this->related[$this->view][substr(
			$control->getUniqueId(),
			strlen($this->getUniqueId()) + 1
		)][$view] = $control->getSnippetId();
	}

	public function setCacheStorage(Nette\Caching\IStorage $storage)
	{
		$this->cache = new Nette\Caching\Cache(
			$storage,
			static::class
		);
	}

	public function handleRedirect(string $fragment = NULL)
	{
		$destination = 'this' . ($fragment ? '#' . $fragment : NULL);
		$parameters = $this->getParameters();
		unset($parameters['fragment']);
		if ($this->getPresenter()->isAjax()) {
			$this->redrawControl();
			$this->getPresenter()->getPayload()->redirect = $this->link(
				$destination,
				$parameters
			);
		} else {
			$this->redirect(
				$destination,
				$parameters
			);
		}
	}

	function __toString() : string
	{
		return $this->render();
	}

	protected function getViews() : array
	{
		return [
			$this->view => TRUE,
		];
	}

	private function render() : string
	{
		$template = $this->getTemplate();
		$template->setFile($this[Ytnuk\Templating\Template\Factory::class][$this->view]);
		if ($template instanceof Nette\Bridges\ApplicationLatte\Template) {
			$template->setParameters(
				$this->cycle(
					'startup',
					TRUE
				) + $this->cycle(self::RENDER_METHOD . ucfirst($this->view))
			);

			return $template->getLatte()->renderToString(
				$template->getFile(),
				$template->getParameters()
			);
		}

		return (string) $template;
	}

	private function cycle(
		$method,
		$once = FALSE
	) : array
	{
		if ($once && isset($this->cycle[$method])) {
			return $this->cycle[$method];
		}
		$result = [];
		if (method_exists(
			$this,
			$method
		)) {
			$result = (array) call_user_func(
				[
					$this,
					$method,
				]
			);
		}
		if ($once) {
			$this->cycle[$method] = $result;
		}

		return $result;
	}
}
