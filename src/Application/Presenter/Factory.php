<?php

namespace Ytnuk\Application\Presenter;

use Nette;
use Ytnuk;

/**
 * Class Factory
 *
 * @package Ytnuk\Application
 */
final class Factory extends Nette\Application\PresenterFactory
{

	/**
	 * @var array
	 */
	private $components;

	/**
	 * @param string $name
	 *
	 * @return Ytnuk\Application\Presenter
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
