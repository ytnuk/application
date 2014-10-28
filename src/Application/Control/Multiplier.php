<?php

namespace WebEdit\Application\Control;

use Nette\Application;

final class Multiplier extends Application\UI\Multiplier
{
	public function __construct(callable $factory)
	{
		parent::__construct($factory);
	}
}
