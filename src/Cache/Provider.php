<?php

namespace Ytnuk\Cache;

/**
 * Interface Provider
 *
 * @package Ytnuk\Cache
 */
interface Provider
{

	/**
	 * @return array
	 */
	public function getCacheTags();

	/**
	 * @return array
	 */
	public function getCacheKey();
}
