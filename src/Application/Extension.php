<?php
namespace Ytnuk\Application;

use Nette;
use Ytnuk;

final class Extension
	extends Nette\DI\CompilerExtension
	implements Ytnuk\Config\Provider
{

	public function getConfigResources() : array
	{
		return [
			Nette\Bridges\ApplicationDI\ApplicationExtension::class => [
				'errorPresenter' => NULL,
				'mapping' => [
					'*' => 'Ytnuk\*\*',
				],
				'scanDirs' => FALSE,
			],
			Nette\Bridges\HttpDI\SessionExtension::class => [
				'debugger' => TRUE,
			],
			Nette\DI\Extensions\DIExtension::class => [
				'debugger' => TRUE,
			],
			Nette\DI\Extensions\DecoratorExtension::class => [
				Control::class => [
					'setup' => [
						'setCacheStorage',
					],
				],
			],
		];
	}
}
