<?php

namespace CCB\JsonFormatter\Behat\Printers;

class JsonPathPrinter
{
	public static $basePath;

	public function __construct($basePath)
	{
		self::$basePath = $basePath;
	}
}