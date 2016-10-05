<?php

namespace CCB\JsonFormatter\Behat;

use Behat\Testwork\EventDispatcher\TestworkEventDispatcher;
use Behat\Testwork\Output\Formatter;
use Symfony\Component\EventDispatcher\Event;

class JsonFormatter implements Formatter
{
	private $name;
	private $description;
	private $outputPrinter;

	private $parameters = [];

	public function __construct($name, $description, JsonOutputPrinter $outputPrinter)
	{
		$this->name = $name;
		$this->description = $description;
		$this->outputPrinter = $outputPrinter;
	}

	public static function getSubscribedEvents()
	{
		return [TestworkEventDispatcher::BEFORE_ALL_EVENTS, 'listenEvent'];
	}

	public function getName()
	{
		return $this->name;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function getOutputPrinter()
	{
		return $this->outputPrinter;
	}

	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	public function getParameter($name)
	{
		return array_key_exists($name, $this->parameters) ? $this->parameters[$name] : null;
	}

	/**
	 * Proxies event to the listener.
	 *
	 * @param Event       $event
	 * @param null|string $eventName
	 */
	public function listenEvent(Event $event, $eventName = null)
	{
		$eventName = $eventName ?: $event->getName();

		$this->outputPrinter->writeln($eventName);
	}
}