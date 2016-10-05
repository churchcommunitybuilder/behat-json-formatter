<?php

namespace CCB\JsonFormatter\Behat\Printers;

use Behat\Behat\Output\Node\Printer\StatisticsPrinter;
use Behat\Behat\Output\Statistics\Statistics;
use Behat\Testwork\Output\Formatter;

class JsonStatisticsPrinter implements StatisticsPrinter
{
	public function printStatistics(Formatter $formatter, Statistics $statistics)
	{
		echo $statistics->getTimer()->getTime()."\n";
	}
}