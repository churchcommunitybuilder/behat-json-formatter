<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\SuitePrinter;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Suite\Suite;

class JsonSuitePrinter implements SuitePrinter
{
	public function __construct()
	{
	}

	public function printHeader(Formatter $formatter, Suite $suite)
	{
		echo "Here\n";
		// TODO: Implement printHeader() method.
	}

	public function printFooter(Formatter $formatter, Suite $suite)
	{
		echo "Here2\n";
		// TODO: Implement printFooter() method.
	}
}