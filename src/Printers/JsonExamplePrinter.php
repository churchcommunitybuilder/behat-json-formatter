<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\ExamplePrinter;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;

class JsonExamplePrinter implements ExamplePrinter
{
	public function printHeader(Formatter $formatter, FeatureNode $feature, ExampleNode $example)
	{
		// TODO: Implement printHeader() method.
	}

	public function printFooter(Formatter $formatter, TestResult $result)
	{
		// TODO: Implement printFooter() method.
	}
}