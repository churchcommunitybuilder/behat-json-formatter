<?php

namespace CCB\JsonFormatter\Behat\Printers2;

use Behat\Behat\Output\Node\Printer\Helper\ResultToStringConverter;
use Behat\Behat\Output\Statistics\PhaseStatistics;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Behat\Testwork\Output\Printer\Factory\OutputFactory;
use Behat\Testwork\Output\Printer\StreamOutputPrinter;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Setup\Setup;

class JsonOutputPrinter extends StreamOutputPrinter
{
	protected static $hasPrintedFeature = false;
	protected static $hasPrintedScenario = false;

	protected $statistics;
	protected $basePath;
	protected $converter;

	protected $featureId = null;
	protected $featureUri = null;
	protected $featureTags;
	protected $before;
	protected $steps;
	protected $after;

	public function __construct(OutputFactory $outputFactory, PhaseStatistics $statistics,
								ResultToStringConverter $converter, $basePath)
	{
		parent::__construct($outputFactory);
		$this->statistics = $statistics;
		$this->converter = $converter;

		$this->basePath = $basePath;
	}

	public function start()
	{
		self::$hasPrintedFeature = false;
		$this->write('[');
	}

	public function complete()
	{
		self::$hasPrintedFeature = false;
		$this->write(']');
	}

	public function addFeature(FeatureNode $feature)
	{
		$hasPreviousFeature = self::$hasPrintedFeature;

		$filename = pathinfo($feature->getFile(), PATHINFO_FILENAME);
		$this->featureId = $this->getId($filename);
		$this->featureUri = str_replace($this->basePath, '', $feature->getFile());
		$featureData = [
			'uri' => $this->featureUri,
			'id' => $this->featureId,
			'keyword' => $feature->getKeyword(),
			'name' => $feature->getTitle(),
			'description' => $feature->getDescription(),
			'line' => $feature->getLine(),
		];

		$this->featureTags = $this->getTags($feature);

		$featureData['tags'] = $this->featureTags;

		$featureData['elements'] = [];

		$json = substr(json_encode($featureData), 0, -2);

		if ($hasPreviousFeature) {
			$this->write(',');
		}

		$this->write($json);
		self::$hasPrintedFeature = true;
		self::$hasPrintedScenario = false;
	}

	public function endFeature()
	{
		$this->write(']}');
	}

	public function beforeScenario()
	{
		if (self::$hasPrintedScenario) {
			$this->write(',');
		}
		$this->before = [];
		$this->steps = [];
		$this->after = [];
	}

	public function afterScenario(ScenarioInterface $scenarioNode)
	{
		$scenarioId = $this->featureId . ';' . $this->getId($scenarioNode->getTitle());

		$scenarioTags = array_merge($this->featureTags, $this->getTags($scenarioNode));

		$scenarioData = [
			'id' => $scenarioId,
			'keyword' => $scenarioNode->getKeyword(),
			'name' => $scenarioNode->getTitle(),
			'description' => '',
			'line' => $scenarioNode->getLine(),
			'type' => strtolower($scenarioNode->getNodeType()),
			'tags' => $scenarioTags,
			'before' => $this->before,
			'steps' => $this->steps,
			'after' => $this->after,
		];

		$this->write(json_encode($scenarioData, JSON_PRETTY_PRINT));

		self::$hasPrintedScenario = true;
	}

	protected function getTags(TaggedNodeInterface $node)
	{
		$tags = [];
		foreach ($node->getTags() as $tag) {
			$tags[] = [
				'name' => '@' . $tag,
				'line' => $node->getLine() - 1, // Behat doesn't keep track of the tag lines
			];
		}
		return $tags;
	}

	public function beforeStep()
	{
		$this->statistics->reset();
		$this->statistics->startTimer();
	}

	public function afterStep(StepNode $stepNode, TestResult $result)
	{
		$stepData = [
			'keyword' => $stepNode->getKeyword(),
			'name' => $stepNode->getText(),
			'line' => $stepNode->getLine(),
			'result' => [
				'status' => $this->converter->convertResultToString($result),
				'duration' => $this->statistics->getTimer()->getTime(),
			],
		];

		if ($result instanceof ExceptionResult) {
			$ex = $result->getException();
			if ($ex !== null) {
				$errorMessage = (string)$ex;
				if (($pos = strpos(strtolower($errorMessage), '[internal function]')) !== false) {
					$errorMessage = substr($errorMessage, 0, $pos);
				}

				$errorMessage .= ' in '.$this->featureUri.':'.$stepNode->getLine();

				$stepData['result']['error_message'] = $errorMessage;
			}
		}

		$this->steps[] = $stepData;
	}

	protected function getId($str)
	{
		return str_replace(['_', ' '], '-', $str);
	}
}