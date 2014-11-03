<?php

namespace WebEdit\Message\Control;

use WebEdit\Message;

/**
 * Interface Factory
 *
 * @package WebEdit\Message
 */
interface Factory
{

	/**
	 * @return Message\Control
	 */
	public function create();
}
