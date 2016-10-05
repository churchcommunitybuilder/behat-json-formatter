<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\ScenarioPrinter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioLikeInterface as Scenario;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;

class JsonScenarioPrinter implements ScenarioPrinter
{
	public static $timing;

	public function printHeader(Formatter $formatter, FeatureNode $feature, Scenario $scenario)
	{

	}

	public function printFooter(Formatter $formatter, TestResult $result)
	{
	}
}