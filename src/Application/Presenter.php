<?php
namespace Ytnuk\Application;

use Nette;
use stdClass;
use VojtechDobes;
use Ytnuk;

abstract class Presenter
	extends Nette\Application\UI\Presenter
{

	/**
	 * @var string
	 * @persistent
	 */
	public $environment;

	/**
	 * @var Ytnuk\Templating\Control\Factory
	 */
	private $templatingControl;

	/**
	 * @var VojtechDobes\NetteAjax\OnResponseHandler
	 */
	private $onResponseHandler;

	/**
	 * @var bool
	 */
	private $redirect = FALSE;

	public function injectApplication(
		Ytnuk\Templating\Control\Factory $templatingControl,
		VojtechDobes\NetteAjax\OnResponseHandler $onResponseHandler
	) {
		$this->templatingControl = $templatingControl;
		$this->onResponseHandler = $onResponseHandler;
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
		}
	}

	public function redirectUrl(
		$url,
		$code = NULL
	) {
		$this->redirect = TRUE;
		try {
			parent::redirectUrl(
				$url,
				$code
			);
		} finally {
			$this->redirect = FALSE;
		}
	}

	public function sendPayload()
	{
		$payload = $this->getPayload();
		if ( ! $this->redirect && isset($payload->redirect)) {
			$this->onResponseHandler->markForward();
		}
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

	protected function createComponentTemplating()
	{
		return $this->templatingControl->create();
	}
}
