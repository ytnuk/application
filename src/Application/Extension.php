<?php
namespace Ytnuk\Application;

use Nette;
use VojtechDobes;

final class Extension
	extends Nette\DI\CompilerExtension
{

	/**
	 * @var bool
	 */
	private $debugMode;

	public function __construct(bool $debugMode = FALSE)
	{
		$this->debugMode = $debugMode;
	}

	public function beforeCompile()
	{
		parent::beforeCompile();
		$decorator = current($this->compiler->getExtensions(Nette\DI\Extensions\DecoratorExtension::class));
		if ($decorator instanceof Nette\DI\Extensions\DecoratorExtension) {
			$decorator->addSetups(Control::class, [
				'setCacheStorage',
			]);
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
			$session->defaults['debugger'] = $this->debugMode;
		}
		$http = current($compiler->getExtensions(Nette\Bridges\HttpDI\HttpExtension::class));
		if ($http instanceof Nette\Bridges\HttpDI\HttpExtension) {
			$http->defaults['headers']['X-Powered-By'] = __NAMESPACE__;
			$contentSecurityPolicy = [
				'default-src' => '\'self\'',
				'form-action' => '\'self\'',
				'img-src' => '\'self\' data:',
			];
			if ($this->debugMode) {
				$contentSecurityPolicy += [
					'script-src' => '\'self\' \'unsafe-inline\' \'unsafe-eval\'',
					'style-src' => '\'self\' \'unsafe-inline\'',
				];
			}
			$http->defaults['headers']['Content-Security-Policy'] = implode(';', array_map(function (
				string $key,
				string $value
			) {
				return implode(' ', [
					$key,
					$value,
				]);
			}, array_keys($contentSecurityPolicy), array_values($contentSecurityPolicy)));
		}
		$di = current($compiler->getExtensions(Nette\DI\Extensions\DIExtension::class));
		if ($di instanceof Nette\DI\Extensions\DIExtension) {
			$di->defaults['debugger'] = $this->debugMode;
		}

		return parent::setCompiler($compiler, $name);
	}
}
