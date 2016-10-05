<?php

namespace CCB\JsonFormatter\Behat\Listeners;

use Behat\Behat\Output\Node\Printer\SuitePrinter;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Symfony\Component\EventDispatcher\Event;

class JsonOutlineStoreListener implements EventListener
{
	private $suitePrinter;

	public function __construct(SuitePrinter $suitePrinter)
	{
		$this->suitePrinter = $suitePrinter;
	}

	public function listenEvent(Formatter $formatter, Event $event, $eventName)
	{
		// TODO: Implement listenEvent() method.
		print_r($eventName);
	}
}