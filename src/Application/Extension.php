<?php
namespace Ytnuk\Application;

use Nette;

final class Extension
	extends Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		parent::loadConfiguration();
		$application = current($this->compiler->getExtensions(Nette\Bridges\ApplicationDI\ApplicationExtension::class));
		if ($application instanceof Nette\Bridges\ApplicationDI\ApplicationExtension) {
			$application->defaults['errorPresenter'] = FALSE;
			$application->defaults['scanDirs'] = FALSE;
			$application->defaults['mapping'] = [
				'*' => 'Ytnuk\*\*',
			];
		}
		$session = current($this->compiler->getExtensions(Nette\Bridges\HttpDI\SessionExtension::class));
		if ($session instanceof Nette\Bridges\HttpDI\SessionExtension) {
			$session->defaults['debugger'] = TRUE;
		}
		$di = current($this->compiler->getExtensions(Nette\DI\Extensions\DIExtension::class));
		if ($di instanceof Nette\DI\Extensions\DIExtension) {
			$di->defaults['debugger'] = TRUE;
		}
	}

	public function beforeCompile()
	{
		parent::beforeCompile();
		$decorator = current($this->compiler->getExtensions(Nette\DI\Extensions\DecoratorExtension::class));
		if ($decorator instanceof Nette\DI\Extensions\DecoratorExtension) {
			$decorator->addSetups(
				Control::class,
				[
					'setCacheStorage',
				]
			);
		}
	}
}
