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

	//TODO: remove below method => default mapping should be to base (Ytnuk) namespace only, EVERY presenter then will be registered as service to allow overwriting in project
	/**
	 * @param string $presenter
	 *
	 * @return string
	 */
	public function formatPresenterClass($presenter)
	{
		$class = parent::formatPresenterClass($presenter);
		if ($class && ! class_exists($class)) {
			$namespace = explode('\\', $class);
			$namespace[key($namespace)] = 'Ytnuk';
			$class = implode('\\', $namespace);
		}

		return $class;
	}
}
