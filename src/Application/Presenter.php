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
		return $this[Ytnuk\Templating\Template\Factory::class][$this->getView()];
	}

	/**
	 * @return Ytnuk\Templating\Template
	 */
	public function formatLayoutTemplateFiles()
	{
		return $this[Ytnuk\Templating\Template\Factory::class][$this->getLayout() ? : 'layout']->disableRewind();
	}

	/**
	 * @param array $components
	 */
	public function setComponents(array $components)
	{
		$this->components = $components;
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
	public function sendPayload()
	{
		if (isset($this->payload->snippets)) {
			uksort($this->payload->snippets, function ($a, $b) {
				return substr_count($a, '-') > substr_count($b, '-');
			});
		}
		parent::sendPayload();
	}

	/**
	 * @inheritdoc
	 */
	protected function createRequest($component, $destination, array $args, $mode)
	{
		if ($destination instanceof Ytnuk\Link\Entity) {
			$component = $this;
			$args += $destination->parameters->get()->fetchPairs('key', 'value');
			$destination = $destination->destination;
			if (isset($args['absolute'])) {
				$destination = ($args['absolute'] ? '//' : NULL) . $destination;
				unset($args['absolute']);
			}
		}

		return parent::createRequest($component, $destination, $args, $mode);
	}

	/**
	 * @inheritdoc
	 */
	protected function beforeRender()
	{
		parent::beforeRender();
		if ($this->snippetMode = $this->isAjax()) {
			$this[Ytnuk\Message\Control::class]->redrawControl();
			if ($this->getRequest()->isMethod('POST') || $this->getParameter('do')) {
				Nette\Bridges\ApplicationLatte\UIMacros::renderSnippets($this, new \stdClass, []);
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
			$component = $this->getContext()->getByType($this->components[$name]);
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
