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

	private $mapping;

	private $fallbackMapping;

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

	public function setMapping(array $mapping)
	{
		$this->mapping = $mapping;

		return parent::setMapping($mapping);
	}

	public function formatPresenterClass($presenter)
	{
		$class = parent::formatPresenterClass($presenter);
		if ( ! class_exists($class)) {
			parent::setMapping(['*' => $this->fallbackMapping]);
			$class = parent::formatPresenterClass($presenter);
		}

		return $class;
	}

	public function unformatPresenterClass($class)
	{
		$presenter = parent::unformatPresenterClass($class);
		parent::setMapping(['*' => $this->mapping['*']]);

		return $presenter;
	}

	public function setFallbackMapping($mapping)
	{
		$this->fallbackMapping = $mapping;
	}
}
