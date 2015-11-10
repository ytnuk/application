<?php
namespace Ytnuk\Application;

use Nette;
use stdClass;
use Ytnuk;

abstract class Presenter
	extends Nette\Application\UI\Presenter
{

	/**
	 * @var Ytnuk\Templating\Control\Factory
	 */
	private $templatingControl;

	/**
	 * @var Ytnuk\Message\Control\Factory
	 */
	private $messageControl;

	public function injectApplication(
		Ytnuk\Templating\Control\Factory $templatingControl,
		Ytnuk\Message\Control\Factory $messageControl
	) {
		$this->templatingControl = $templatingControl;
		$this->messageControl = $messageControl;
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
			if ($absolute = $args['absolute'] ?? NULL) {
				$destination = ($absolute ? '//' : NULL) . $destination;
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
		$template = $this[Ytnuk\Templating\Control::NAME][$this->getLayout() ? : 'layout'];

		return $template instanceof Ytnuk\Templating\Template ? $template->disableRewind() : parent::formatLayoutTemplateFiles();
	}

	public function formatTemplateFiles() : Ytnuk\Templating\Template
	{
		return $this[Ytnuk\Templating\Control::NAME][$this->getView()] ? : parent::formatTemplateFiles();
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

	public function redrawControl(
		string $snippet = NULL,
		bool $redraw = TRUE
	) {
		parent::redrawControl(
			$snippet,
			$redraw
		);
		$this[Ytnuk\Message\Control::NAME]->redrawControl();
	}

	public function sendPayload()
	{
		$payload = $this->getPayload();
		if ($payload && $snippets = (array) $payload->snippets ?? []) {
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

	protected function createComponentTemplating()
	{
		return $this->templatingControl->create();
	}

	protected function createComponentMessage()
	{
		return $this->messageControl->create();
	}
}
