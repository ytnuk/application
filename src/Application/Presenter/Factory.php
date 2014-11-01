<?php

namespace WebEdit\Application\Presenter;

use Nette\Application\PresenterFactory;
use WebEdit\Application;

/**
 * Class Factory
 *
 * @package WebEdit\Application
 */
final class Factory extends PresenterFactory
{

	/**
	 * @var array
	 */
	private $components;

	/**
	 * @param string $name
	 *
	 * @return Application\Presenter
	 */
	public function createPresenter($name)
	{
		$presenter = parent::createPresenter($name);
		$presenter->setComponents($this->components);

		return $presenter;
	}

	/**
	 * @param array $components
	 */
	public function setComponents(array $components)
	{
		$this->components = $components;
	}
}
