<?php
namespace Ytnuk\Application;

use Nette;
use Ytnuk;

/**
 * Class Presenter
 *
 * @package Ytnuk\Application
 */
abstract class Presenter
	extends Nette\Application\UI\Presenter
{

	/**
	 * @var array
	 */
	private $components;

	/**
	 * @var Nette\DI\Container
	 */
	private $container;

	/**
	 * @param array $components
	 */
	public function setComponents(array $components)
	{
		$this->components = $components;
	}

	/**
	 * @param \Nette\DI\Container $container
	 */
	public function injectContainer(Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @inheritdoc
	 */
	protected function beforeRender()
	{
		parent::beforeRender();
		if ($this->snippetMode = $this->isAjax()) {
			if ($this->getRequest()->isMethod(Nette\Http\IRequest::POST) || $this->getParameter(self::SIGNAL_KEY)) {
				Nette\Bridges\ApplicationLatte\UIRuntime::renderSnippets(
					$this,
					new \stdClass,
					[]
				);
				$this->sendPayload();
			} else {
				$this->redrawControl();
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function createComponent($name)
	{
		$component = parent::createComponent($name);
		if ( ! $component && isset($this->components[$name])) {
			$component = $this->container->getByType($this->components[$name]);
			if (method_exists(
				$component,
				'create'
			)) {
				$component = call_user_func(
					[
						$component,
						'create',
					]
				);
			}
		}

		return $component;
	}

	/**
	 * @inheritdoc
	 */
	protected function createRequest(
		$component,
		$destination,
		array $args,
		$mode
	) {
		if ($destination instanceof Ytnuk\Link\Entity) {
			$component = $this;
			$args += $destination->parameters->get()->fetchPairs(
				'key',
				'value'
			);
			$destination = $destination->destination;
			if (isset($args['absolute'])) {
				$destination = ($args['absolute'] ? '//' : NULL) . $destination;
				unset($args['absolute']);
			}
		}

		return parent::createRequest(
			$component,
			$destination,
			$args,
			$mode
		);
	}

	/**
	 * @return Ytnuk\Templating\Template
	 */
	public function formatLayoutTemplateFiles()
	{
		$template = $this[Ytnuk\Templating\Template\Factory::class][$this->getLayout() ? : 'layout'];

		return $template instanceof Ytnuk\Templating\Template ? $template->disableRewind() : parent::formatLayoutTemplateFiles();
	}

	/**
	 * @return Ytnuk\Templating\Template
	 */
	public function formatTemplateFiles()
	{
		return $this[Ytnuk\Templating\Template\Factory::class][$this->getView()] ? : parent::formatTemplateFiles();
	}

	/**
	 * @inheritdoc
	 */
	public function getComponent(
		$name,
		$need = TRUE
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

	/**
	 * @inheritdoc
	 */
	public function sendPayload()
	{
		if (isset($this->payload->snippets)) {
			uksort(
				$this->payload->snippets,
				function (
					$a,
					$b
				) {
					return substr_count(
						$a,
						'-'
					) > substr_count(
						$b,
						'-'
					);
				}
			);
		}
		parent::sendPayload();
	}
}
