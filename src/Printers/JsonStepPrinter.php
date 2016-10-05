<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\ScenarioLikeInterface as Scenario;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Output\Formatter;

class JsonStepPrinter implements StepPrinter
{
	public function printStep(Formatter $formatter, Scenario $scenario, StepNode $step, StepResult $result)
	{
		//print_r($scenario);
		//print_r($formatter);
	}
}