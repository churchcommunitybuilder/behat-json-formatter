<?php

namespace CCB\JsonFormatter\Behat;

use Behat\Behat\Output\Printer\ConsoleOutputFactory;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\Output\Node\EventListener\ChainEventListener;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use CCB\JsonFormatter\Behat\Listeners\JsonListener;
use CCB\JsonFormatter\Behat\Printers\JsonOutputPrinter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BehatJsonFormatterExtension implements Extension
{
	/**
	 * @var ServiceProcessor
	 */
	private $processor;

	/*
	 * Available services
	 */
	const ROOT_LISTENER_ID = 'output.node.listener.json';
	const RESULT_TO_STRING_CONVERTER_ID = 'output.node.printer.result_to_string';

	/*
	 * Available extension points
	 */
	const ROOT_LISTENER_WRAPPER_TAG = 'output.node.listener.json.wrapper';

	/**
	 * Initializes extension.
	 *
	 * @param null|ServiceProcessor $processor
	 */
	public function __construct(ServiceProcessor $processor = null)
	{
		$this->processor = $processor ?: new ServiceProcessor();
	}

	public function process(ContainerBuilder $container)
	{
		$this->processListenerWrappers($container);
	}

	public function getConfigKey()
	{
		return 'json';
	}

	public function initialize(ExtensionManager $extensionManager)
	{
	}

	public function configure(ArrayNodeDefinition $builder)
	{
	}

	public function load(ContainerBuilder $container, array $config)
	{
		$this->loadRootNodeListener($container);

		$this->loadPrinterHelpers($container);

		$this->loadFormatter($container);
	}

	/**
	 * {@inheritdoc}
	 */
	public function processFormatter(ContainerBuilder $container)
	{
		$this->processListenerWrappers($container);
	}

	/**
	 * Loads printer helpers.
	 *
	 * @param ContainerBuilder $container
	 */
	private function loadPrinterHelpers(ContainerBuilder $container)
	{
		$definition = new Definition('Behat\Behat\Output\Node\Printer\Helper\ResultToStringConverter');
		$container->setDefinition(self::RESULT_TO_STRING_CONVERTER_ID, $definition);
	}

	/**
	 * Loads json formatter node event listener.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadRootNodeListener(ContainerBuilder $container)
	{
		$definition = new Definition(JsonListener::class);
		$container->setDefinition('output.node.listener.junit.all', $definition);

		$definition = new Definition(ChainEventListener::class, [
			[
				new Reference('output.node.listener.junit.all'),
			]
		]);
		$container->setDefinition(self::ROOT_LISTENER_ID, $definition);
	}

	/**
	 * Loads formatter itself.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadFormatter(ContainerBuilder $container)
	{
		$definition = new Definition('Behat\Behat\Output\Statistics\PhaseStatistics');
		$container->setDefinition('output.json.statistics', $definition);

		$definition = new Definition('Behat\Testwork\Output\NodeEventListeningFormatter', array(
			'json',
			'Outputs in json.',
			array(
				'timer'     => true,
				'expand'    => false,
				'paths'     => true,
				'multiline' => false,
			),
			$this->createOutputPrinterDefinition(),
			new Definition(
				'Behat\Testwork\Output\Node\EventListener\ChainEventListener',
				array(
					array(
						$this->rearrangeBackgroundEvents(
							new Reference(self::ROOT_LISTENER_ID)
						),
						new Definition('Behat\Behat\Output\Node\EventListener\Statistics\ScenarioStatsListener', array(
							new Reference('output.junit.statistics')
						)),
						new Definition('Behat\Behat\Output\Node\EventListener\Statistics\StepStatsListener', array(
							new Reference('output.junit.statistics'),
							new Reference(ExceptionExtension::PRESENTER_ID)
						)),
						new Definition('Behat\Behat\Output\Node\EventListener\Statistics\HookStatsListener', array(
							new Reference('output.junit.statistics'),
							new Reference(ExceptionExtension::PRESENTER_ID)
						)),
					)
				)
			)
		));
		$definition->addTag(OutputExtension::FORMATTER_TAG, array('priority' => 100));
		$container->setDefinition(OutputExtension::FORMATTER_TAG . '.json', $definition);
	}

	/**
	 * Creates output printer definition.
	 *
	 * @return Definition
	 */
	protected function createOutputPrinterDefinition()
	{
		return new Definition(JsonOutputPrinter::class, [
			new Definition(ConsoleOutputFactory::class),
			new Reference('output.json.statistics'),
			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
			'%paths.base%',
		]);
	}

	/**
	 * Creates root listener definition.
	 *
	 * @param mixed $listener
	 *
	 * @return Definition
	 */
	protected function rearrangeBackgroundEvents($listener)
	{
		return new Definition('Behat\Behat\Output\Node\EventListener\Flow\FirstBackgroundFiresFirstListener', array(
			new Definition('Behat\Behat\Output\Node\EventListener\Flow\OnlyFirstBackgroundFiresListener', array(
				$listener
			))
		));
	}

	/**
	 * Processes all registered json formatter node listener wrappers.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function processListenerWrappers(ContainerBuilder $container)
	{
		$this->processor->processWrapperServices($container, self::ROOT_LISTENER_ID, self::ROOT_LISTENER_WRAPPER_TAG);
	}
}
