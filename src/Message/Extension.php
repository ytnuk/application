<?php

namespace WebEdit\Message;

use Nette\DI;
use WebEdit\Application;
use WebEdit\Config;

/**
 * Class Extension
 *
 * @package WebEdit\Message
 */
final class Extension extends DI\CompilerExtension implements Config\Provider
{

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			'services' => [
				'message' => [
					'implement' => Control\Factory::class,
					'tags' => [Application\Extension::COMPONENT_TAG]
				]
			]
		];
	}
}
