<?php

namespace WebEdit\Application\Control;

use Nette\Application;

/**
 * Class Multiplier
 *
 * @package WebEdit\Application
 */
final class Multiplier extends Application\UI\Multiplier
{

	/**
	 * @param callable $factory
	 */
	public function __construct(callable $factory)
	{
		parent::__construct($factory);
	}
}
