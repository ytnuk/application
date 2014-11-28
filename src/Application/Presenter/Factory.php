<?php

namespace Kutny\Application\Presenter;

use Nette;
use Kutny;

/**
 * Class Factory
 *
 * @package Kutny\Application
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
	 * @return Kutny\Application\Presenter
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

	/**
	 * @param $class
	 *
	 * @return string
	 */
	public function unformatPresenterClass($class)
	{
		if ( ! $presenter = parent::unformatPresenterClass($class)) {
			$namespace = explode('\\', $class);
			array_shift($namespace);
			$presenter = implode(':', $namespace);
		}

		return $presenter;
	}

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
			$namespace[key($namespace)] = 'Kutny';
			$class = implode('\\', $namespace);
		}

		return $class;
	}
}
