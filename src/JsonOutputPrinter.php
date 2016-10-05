<?php

namespace CCB\JsonFormatter\Behat;

use Behat\Testwork\Output\Printer\Factory\OutputFactory;
use Behat\Testwork\Output\Printer\StreamOutputPrinter;

class JsonOutputPrinter extends StreamOutputPrinter
{
	public function __construct(OutputFactory $outputFactory)
	{
		parent::__construct($outputFactory);
	}

}