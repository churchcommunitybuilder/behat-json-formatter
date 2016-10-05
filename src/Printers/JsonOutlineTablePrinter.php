<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\OutlineTablePrinter;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;

class JsonOutlineTablePrinter implements OutlineTablePrinter
{
	public function printHeader(Formatter $formatter, FeatureNode $feature, OutlineNode $outline, array $results)
	{
		// TODO: Implement printHeader() method.
	}

	public function printFooter(Formatter $formatter, TestResult $result)
	{
		// TODO: Implement printFooter() method.
	}
}