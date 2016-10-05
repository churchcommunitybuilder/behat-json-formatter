<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\OutlinePrinter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;

class JsonOutlinePrinter implements OutlinePrinter
{
	public function printHeader(Formatter $formatter, FeatureNode $feature, OutlineNode $outline)
	{
		// TODO: Implement printHeader() method.
	}

	public function printFooter(Formatter $formatter, TestResult $result)
	{
		// TODO: Implement printFooter() method.
	}
}