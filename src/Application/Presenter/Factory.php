<?php
namespace Ytnuk\Application\Presenter;

use Nette;
use Ytnuk;

final class Factory
	extends Nette\Application\PresenterFactory
{

	/**
	 * @var array
	 */
	private $components = [];

	public function createPresenter($name) : Nette\Application\IPresenter
	{
		$presenter = parent::createPresenter($name);
		if ($presenter instanceof Ytnuk\Application\Presenter) {
			$presenter->setComponents($this->components);
		}

		return $presenter;
	}

	public function setComponents(array $components)
	{
		$this->components = $components;
	}
}
