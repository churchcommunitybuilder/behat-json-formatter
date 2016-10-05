<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\FeaturePrinter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;

class JsonFeaturePrinter implements FeaturePrinter
{
	private $featureData;
	public function printHeader(Formatter $formatter, FeatureNode $feature)
	{
		$basePath = JsonPathPrinter::$basePath;

		$filename = pathinfo($feature->getFile(), PATHINFO_FILENAME);
		$featureId = $this->getId($filename);

		$this->featureData = [
			'uri' => str_replace($basePath.'/', '', $feature->getFile()),
			'id' => $featureId,
			'keyword' => $feature->getKeyword(),
			'name' => $feature->getTitle(),
			'description' => $feature->getDescription(),
			'line' => $feature->getLine(),
		];

		$featureTags = [];
		foreach ($feature->getTags() as $tag) {
			$featureTags[] = [
				'name' => $tag,
				'line' => $feature->getLine() - 1, //Hard coded here
			];
		}

		$this->featureData['tags'] = $featureTags;

		$elements = [];
		foreach ($feature->getScenarios() as $scenario) {
			$scenarioId = $featureId.';'.$this->getId($scenario->getTitle());

			$scenarioTags = $featureTags;
			foreach ($scenario->getTags() as $tag) {
				$scenarioTags[] = [
					'name' => $tag,
					'line' => $scenario->getLine() - 1, //Hard coded here
				];
			}

			$before = [];
			$steps = [];
			$after = [];
			foreach ($scenario->getSteps() as $step) {
				if ($step->getNodeType() == 'Step') {
					$step = [
						'keyword' => $step->getKeyword(),
						'name' => $step->getText(),
						'line' => $step->getLine(),
						'result' => null,
					];
					$steps[] = $step;
				}
			}

			$element = [
				'id' => $scenarioId,
				'keyword' => $scenario->getKeyword(),
				'name' => $scenario->getTitle(),
				'description' => '',
				'line' => $scenario->getLine(),
				'type' => strtolower($scenario->getNodeType()),
				'tags' => $scenarioTags,
				'before' => $before,
				'steps' => $steps,
				'after' => $after,
			];
			$elements[] = $element;
		}
		$this->featureData['elements'] = $elements;

		// TODO: Implement printHeader() method.
	}

	public function printFooter(Formatter $formatter, TestResult $result)
	{
		//echo json_encode($this->featureData);
		//echo "\n";
		print_r($this->featureData);
		//$result->
	}

	private function getId($str)
	{
		return str_replace(['_', ' '], ['-', '-'], $str);
	}
}