<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\Output\Node\Printer\Helper\ResultToStringConverter;
use Behat\Behat\Output\Statistics\PhaseStatistics;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\EventDispatcher\Event\AfterSetup;
use Behat\Testwork\Hook\Tester\Setup\HookedSetup;
use Behat\Testwork\Hook\Tester\Setup\HookedTeardown;
use Behat\Testwork\Output\Printer\Factory\OutputFactory;
use Behat\Testwork\Output\Printer\StreamOutputPrinter;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Setup\Teardown;

class JsonOutputPrinter extends StreamOutputPrinter
{
	protected static $hasPrintedFeature = false;
	protected static $hasPrintedScenario = false;

	protected $statistics;
	protected $basePath;
	protected $converter;

	protected $featureId = null;
	protected $featureUri = null;
	protected $scenarioLine = null;
	protected $featureTags;
	protected $before;
	protected $steps;
	protected $after;

	protected $setup;

	public function __construct(
		OutputFactory $outputFactory,
		PhaseStatistics $statistics,
		ResultToStringConverter $converter,
		$basePath
	) {
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

	public function endFeature(AfterFeatureTested $afterFeature)
	{
		error_log('Passed: ' . ($afterFeature->getTestResult()->isPassed() ? 'true' : 'false'));
		$this->write(']}');
	}

	public function beforeScenario(ScenarioInterface $scenarioNode)
	{
		if (self::$hasPrintedScenario) {
			$this->write(',');
		}
		$this->before = [];
		$this->steps = [];
		$this->after = [];
		$this->setup = [];

		$this->scenarioLine = $scenarioNode->getLine();
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

	public function afterStep(
		StepNode $stepNode,
		TestResult $result,
		Teardown $teardown
	) {
		$stepData = [
			'keyword' => $stepNode->getKeyword(),
			'name' => $stepNode->getText(),
			'line' => $stepNode->getLine(),
			'result' => [
				'status' => $this->converter->convertResultToString($result),
				'duration' => $this->statistics->getTimer()->getTime(),
			],
		];

		$appendException = function (\Throwable $ex) use ($stepNode, &$stepData) {
			$featureLine = ' in ' . $this->featureUri . ':' . ($this->scenarioLine ?? $stepNode->getLine());

			$exceptionTrace = $ex->getTrace();

			$trace = array_map(function ($trace) {
				$file = $trace['file'] ?? '';
				$line = $trace['line'] ?? '';

				if ($file === '' && $line === '') {
					return null;
				}

				if (str_contains($file, '/vendor/')) {
					return null;
				}

				return "{$file}:{$line}";
			}, $exceptionTrace);

			$errorMessage = get_class($ex) . ': ' . $ex->getMessage() . ' in ' . PHP_EOL;

			$errorMessage .= implode(PHP_EOL, array_filter($trace));

			$errorMessage .= PHP_EOL . $featureLine;

			$stepData['result']['error_message'] = $errorMessage;
		};

		if ($result instanceof ExceptionResult && $result->hasException()) {
			$appendException($result->getException());

		} elseif ($teardown instanceof HookedTeardown && !$teardown->isSuccessful()) {
			$stepData['result']['status'] = 'failed';

			foreach ($teardown->getHookCallResults() as $callResult) {
				/** @var CallResult $callResult */
				if ($callResult->hasException()) {
					$appendException($callResult->getException());
				}
			}
		} elseif (!empty($this->after)) {
			foreach ($this->after as $after) {
				/** @var AfterSetup $after */
				$setup = $after->getSetup();
				if ($setup instanceof HookedSetup) {
					foreach ($setup->getHookCallResults() as $callResult) {
						/** @var CallResult $callResult */
						if ($callResult->hasException()) {
							$appendException($callResult->getException());
						}
					}
				}
			}
		}

		$this->steps[] = $stepData;
	}

	public function afterSetup(AfterSetup $afterSetup)
	{
		$this->after[] = $afterSetup;
	}

	protected function getId($str)
	{
		return str_replace(['_', ' '], '-', $str);
	}
}
