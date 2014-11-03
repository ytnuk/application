<?php

namespace WebEdit\Message;

use WebEdit\Application;

/**
 * Class Control
 *
 * @package WebEdit\Message
 */
final class Control extends Application\Control
{

	protected function startup()
	{
		$this->template->messages = $this->parent->template->flashes;
	}
}
