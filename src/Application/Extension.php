<?php
namespace Ytnuk\Application;

use Nette;
use VojtechDobes;

final class Extension
	extends Nette\DI\CompilerExtension
{

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

	public function setCompiler(
		Nette\DI\Compiler $compiler,
		$name
	) : self
	{
		$application = current($compiler->getExtensions(Nette\Bridges\ApplicationDI\ApplicationExtension::class));
		if ($application instanceof Nette\Bridges\ApplicationDI\ApplicationExtension) {
			$application->defaults['errorPresenter'] = FALSE;
			$application->defaults['scanDirs'] = FALSE;
			$application->defaults['mapping'] = [
				'*' => 'Ytnuk\*\*',
			];
		}
		$session = current($compiler->getExtensions(Nette\Bridges\HttpDI\SessionExtension::class));
		if ($session instanceof Nette\Bridges\HttpDI\SessionExtension) {
			$session->defaults['debugger'] = TRUE;
		}
		$di = current($compiler->getExtensions(Nette\DI\Extensions\DIExtension::class));
		if ($di instanceof Nette\DI\Extensions\DIExtension) {
			$di->defaults['debugger'] = TRUE;
		}
		$compiler->addExtension(
			'vojtechDobes.history',
			new VojtechDobes\NetteAjax\HistoryExtension
		);

		return parent::setCompiler(
			$compiler,
			$name
		);
	}
}
