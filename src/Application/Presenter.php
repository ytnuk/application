<?php
namespace Ytnuk\Application;

use Nette;
use stdClass;
use Ytnuk;

abstract class Presenter
	extends Nette\Application\UI\Presenter
{

	/**
	 * @var array
	 */
	private $components = [];

	/**
	 * @var Nette\DI\Container
	 */
	private $container;

	public function setComponents(array $components)
	{
		$this->components = $components;
	}

	public function injectContainer(Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	protected function createComponent($name) : Nette\ComponentModel\IComponent
	{
		$component = parent::createComponent($name);
		if ( ! $component && isset($this->components[$name])) {
			$component = $this->container->getByType($this->components[$name]);
			if ($component instanceof Ytnuk\Application\Control\Factory) {
				$component = $component->create();
			}
		}

		return $component;
	}

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

	public function formatLayoutTemplateFiles() : Ytnuk\Templating\Template
	{
		$template = $this[Ytnuk\Templating\Template\Factory::class][$this->getLayout() ? : 'layout'];

		return $template instanceof Ytnuk\Templating\Template ? $template->disableRewind() : parent::formatLayoutTemplateFiles();
	}

	public function formatTemplateFiles() : Ytnuk\Templating\Template
	{
		return $this[Ytnuk\Templating\Template\Factory::class][$this->getView()] ? : parent::formatTemplateFiles();
	}

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

	public function redrawControl(
		string $snippet = NULL,
		bool $redraw = TRUE
	) {
		parent::redrawControl(
			$snippet,
			$redraw
		);
		foreach ($this->components as $name => $factory) {
			if (isset($this[$name]) && $component = $this[$name]) {
				if ($component instanceof Nette\Application\UI\IRenderable) {
					$component->redrawControl();
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function processSignal()
	{
		$signal = $this->getSignal();
		parent::processSignal();
		if ($signal && $this->snippetMode = $this->isAjax()) {
			Nette\Bridges\ApplicationLatte\UIRuntime::renderSnippets(
				$this,
				new stdClass,
				[]
			);
			$this->sendPayload();
		} else {
			$this->redrawControl();
		}
	}

	public function sendPayload()
	{
		$payload = $this->getPayload();
		if ($payload && isset($payload->snippets) && $snippets = (array) $payload->snippets) {
			ksort($snippets);
			uksort(
				$snippets,
				function (
					$left,
					$right
				) {
					return substr_count(
						$left,
						'-'
					) <=> substr_count(
						$right,
						'-'
					);
				}
			);
			$payload->snippets = $snippets;
		}
		parent::sendPayload();
	}
}
