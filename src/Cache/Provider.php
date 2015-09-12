<?php
namespace Ytnuk\Cache;

interface Provider
{

	public function getCacheTags() : array;

	public function getCacheKey() : array;
}
