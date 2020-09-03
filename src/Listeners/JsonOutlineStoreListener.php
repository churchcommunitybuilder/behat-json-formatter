<?php

namespace CCB\JsonFormatter\Behat\Listeners;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Testwork\Event\Event;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use CCB\JsonFormatter\Behat\Printers2\JsonOutputPrinter;

class JsonOutlineStoreListener implements EventListener
{
	public function listenEvent(Formatter $formatter, Event $event, $eventName)
	{
		$this->beforeExerciseCompleted($formatter, $event);
		$this->afterExerciseCompleted($formatter, $event);
		$this->beforeFeatureTested($formatter, $event);
		$this->afterFeatureTested($formatter, $event);
		$this->beforeScenarioTested($formatter, $event);
		$this->afterScenarioTested($formatter, $event);
		$this->beforeStepTested($formatter, $event);
		$this->afterStepTested($formatter, $event);

//		echo $eventName.' => '.get_class($event)."\n";
	}

	protected function beforeExerciseCompleted(Formatter $formatter, Event $event)
	{
		if (!$event instanceof BeforeExerciseCompleted) {
			return;
		}

		$this->getOutputPrinter($formatter)->start();
	}

	protected function afterExerciseCompleted(Formatter $formatter, Event $event)
	{
		if (!$event instanceof AfterExerciseCompleted) {
			return;
		}

		$this->getOutputPrinter($formatter)->complete();
	}

	protected function beforeFeatureTested(Formatter $formatter, Event $event)
	{
		if (!$event instanceof BeforeFeatureTested) {
			return;
		}

		$this->getOutputPrinter($formatter)->addFeature($event->getFeature());
	}

	protected function afterFeatureTested(Formatter $formatter, Event $event)
	{
		if (!$event instanceof AfterFeatureTested) {
			return;
		}

		$this->getOutputPrinter($formatter)->endFeature();
	}

	protected function beforeScenarioTested(Formatter $formatter, Event $event)
	{
		if (!$event instanceof BeforeScenarioTested) {
			return;
		}

		$this->getOutputPrinter($formatter)->beforeScenario();
	}

	protected function afterScenarioTested(Formatter $formatter, Event $event)
	{
		if (!$event instanceof AfterScenarioTested) {
			return;
		}

		$this->getOutputPrinter($formatter)->afterScenario($event->getScenario());
	}

	protected function beforeStepTested(Formatter $formatter, Event $event)
	{
		if (!$event instanceof BeforeStepTested) {
			return;
		}

		$this->getOutputPrinter($formatter)->beforeStep();
	}

	protected function afterStepTested(Formatter $formatter, Event $event)
	{
		if (!$event instanceof AfterStepTested) {
			return;
		}

		$this->getOutputPrinter($formatter)->afterStep($event->getStep(), $event->getTestResult());
	}

	/**
	 * @param Formatter $formatter
	 * @return JsonOutputPrinter
	 */
	protected function getOutputPrinter(Formatter $formatter)
	{
		$printer = $formatter->getOutputPrinter();
		if (!$printer instanceof JsonOutputPrinter) {
			throw new \RuntimeException('Output printer must be instance of '.JsonOutputPrinter::class);
		}

		return $printer;
	}
}
