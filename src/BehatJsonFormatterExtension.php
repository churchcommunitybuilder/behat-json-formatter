<?php

namespace CCB\JsonFormatter\Behat;

use Behat\Behat\Definition\ServiceContainer\DefinitionExtension;
use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\Output\Node\EventListener\AST\FeatureListener;
use Behat\Behat\Output\Node\EventListener\AST\OutlineListener;
use Behat\Behat\Output\Node\EventListener\AST\OutlineTableListener;
use Behat\Behat\Output\Node\EventListener\AST\ScenarioNodeListener;
use Behat\Behat\Output\Node\EventListener\AST\StepListener;
use Behat\Behat\Output\Node\EventListener\AST\SuiteListener;
use Behat\Behat\Output\Node\EventListener\Flow\FireOnlySiblingsListener;
use Behat\Behat\Output\Node\EventListener\Flow\FirstBackgroundFiresFirstListener;
use Behat\Behat\Output\Node\EventListener\Flow\OnlyFirstBackgroundFiresListener;
use Behat\Behat\Output\Node\EventListener\Statistics\HookStatsListener;
use Behat\Behat\Output\Node\EventListener\Statistics\ScenarioStatsListener;
use Behat\Behat\Output\Node\EventListener\Statistics\StepStatsListener;
use Behat\Behat\Output\Node\Printer\CounterPrinter;
use Behat\Behat\Output\Node\Printer\Helper\ResultToStringConverter;
use Behat\Behat\Output\Node\Printer\Helper\StepTextPainter;
use Behat\Behat\Output\Node\Printer\ListPrinter;
use Behat\Behat\Output\Statistics\TotalStatistics;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\Output\Node\EventListener\ChainEventListener;
use Behat\Testwork\Output\Node\EventListener\Flow\FireOnlyIfFormatterParameterListener;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\Factory\FilesystemOutputFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Behat\Testwork\Translator\ServiceContainer\TranslatorExtension;
use CCB\JsonFormatter\Behat\Printers\JsonExamplePrinter;
use CCB\JsonFormatter\Behat\Printers\JsonExampleRowPrinter;
use CCB\JsonFormatter\Behat\Printers\JsonFeaturePrinter;
use CCB\JsonFormatter\Behat\Printers\JsonOutlinePrinter;
use CCB\JsonFormatter\Behat\Printers\JsonOutlineTablePrinter;
use CCB\JsonFormatter\Behat\Printers\JsonPathPrinter;
use CCB\JsonFormatter\Behat\Printers\JsonScenarioPrinter;
use CCB\JsonFormatter\Behat\Printers\JsonSetupPrinter;
use CCB\JsonFormatter\Behat\Printers\JsonSkippedStepPrinter;
use CCB\JsonFormatter\Behat\Printers\JsonStatisticsPrinter;
use CCB\JsonFormatter\Behat\Printers\JsonStepPrinter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BehatJsonFormatterExtension implements Extension
{
	/*
	 * Available services
	 */
	const ROOT_LISTENER_ID = 'output.node.listener.json';
	const RESULT_TO_STRING_CONVERTER_ID = 'output.node.printer.result_to_string';

	/*
	 * Available extension points
	 */
	const ROOT_LISTENER_WRAPPER_TAG = 'output.node.listener.json.wrapper';

	private $processor;
	/**
	 * Initializes extension.
	 *
	 * @param null|ServiceProcessor $processor
	 */
	public function __construct(ServiceProcessor $processor = null)
	{
		$this->processor = $processor ? : new ServiceProcessor();
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

		$this->loadCorePrinters($container);
		$this->loadTableOutlinePrinter($container);
		$this->loadTableOutlinePrinter($container);
		$this->loadExpandedOutlinePrinter($container);
		$this->loadHookPrinters($container);
		$this->loadStatisticsPrinter($container);
		$this->loadPrinterHelpers($container);
		$this->loadFormatter($container);
	}

	/**
	 * Loads json formatter node event listener.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadRootNodeListener(ContainerBuilder $container)
	{
		$definition = new Definition(ChainEventListener::class, [
			[
				new Definition(SuiteListener::class, [
					new Reference('output.node.printer.json.suite_setup')
				]),
				new Definition(FeatureListener::class, [
					new Reference('output.node.printer.json.feature'),
					new Reference('output.node.printer.json.feature_setup')
				]),
				$this->proxySiblingEvents(
					BackgroundTested::BEFORE,
					BackgroundTested::AFTER,
					[
						new Definition(ScenarioNodeListener::class, [
							BackgroundTested::AFTER_SETUP,
							BackgroundTested::AFTER,
							new Reference('output.node.printer.json.scenario')
						]),
						new Definition(StepListener::class, [
							new Reference('output.node.printer.json.step'),
							new Reference('output.node.printer.json.step_setup')
						]),
					]
				),
				$this->proxySiblingEvents(
					ScenarioTested::BEFORE,
					ScenarioTested::AFTER,
					[
						new Definition(ScenarioNodeListener::class, [
							ScenarioTested::AFTER_SETUP,
							ScenarioTested::AFTER,
							new Reference('output.node.printer.json.scenario'),
							new Reference('output.node.printer.json.scenario_setup')
						]),
						new Definition(StepListener::class, [
							new Reference('output.node.printer.json.step'),
							new Reference('output.node.printer.json.step_setup')
						]),
					]
				),
				$this->proxySiblingEvents(
					OutlineTested::BEFORE,
					OutlineTested::AFTER,
					[
						$this->proxyEventsIfParameterIsSet(
							'expand',
							false,
							new Definition(OutlineTableListener::class, [
								new Reference('output.node.printer.json.outline_table'),
								new Reference('output.node.printer.json.example_row'),
								new Reference('output.node.printer.json.example_setup'),
								new Reference('output.node.printer.json.example_step_setup')
							])
						),
						$this->proxyEventsIfParameterIsSet(
							'expand',
							true,
							new Definition(OutlineListener::class, [
								new Reference('output.node.printer.json.outline'),
								new Reference('output.node.printer.json.example'),
								new Reference('output.node.printer.json.example_step'),
								new Reference('output.node.printer.json.example_setup'),
								new Reference('output.node.printer.json.example_step_setup')
							])
						)
					]
				),
			]
		]);
		$container->setDefinition(self::ROOT_LISTENER_ID, $definition);
	}

	/**
	 * Loads feature, scenario and step printers.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadCorePrinters(ContainerBuilder $container)
	{
		$definition = new Definition(JsonFeaturePrinter::class);
		$container->setDefinition('output.node.printer.json.feature', $definition);

		$definition = new Definition(JsonPathPrinter::class, [
			'%paths.base%'
		]);
		$container->setDefinition('output.node.printer.json.path', $definition);

		$definition = new Definition(JsonScenarioPrinter::class, [
			new Reference('output.node.printer.json.path'),
		]);
		$container->setDefinition('output.node.printer.json.scenario', $definition);

		$definition = new Definition(JsonStepPrinter::class, [
//			new Reference('output.node.printer.json.step_text_painter'),
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference('output.node.printer.json.path'),
//			new Reference(ExceptionExtension::PRESENTER_ID)
		]);
		$container->setDefinition('output.node.printer.json.step', $definition);

		$definition = new Definition(JsonSkippedStepPrinter::class, [
//			new Reference('output.node.printer.json.step_text_painter'),
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference('output.node.printer.json.path'),
		]);
		$container->setDefinition('output.node.printer.json.skipped_step', $definition);
	}

	/**
	 * Loads table outline printer.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadTableOutlinePrinter(ContainerBuilder $container)
	{
		$definition = new Definition(JsonOutlineTablePrinter::class, [
//			new Reference('output.node.printer.json.scenario'),
//			new Reference('output.node.printer.json.skipped_step'),
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID)
		]);
		$container->setDefinition('output.node.printer.json.outline_table', $definition);

		$definition = new Definition(JsonExampleRowPrinter::class, [
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference(ExceptionExtension::PRESENTER_ID)
		]);
		$container->setDefinition('output.node.printer.json.example_row', $definition);
	}

	/**
	 * Loads expanded outline printer.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadExpandedOutlinePrinter(ContainerBuilder $container)
	{
		$definition = new Definition(JsonOutlinePrinter::class, [
//			new Reference('output.node.printer.json.scenario'),
//			new Reference('output.node.printer.json.skipped_step'),
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID)
		]);
		$container->setDefinition('output.node.printer.json.outline', $definition);

		$definition = new Definition(JsonExamplePrinter::class, [
			new Reference('output.node.printer.json.path'),
		]);
		$container->setDefinition('output.node.printer.json.example', $definition);

		$definition = new Definition(JsonStepPrinter::class, [
//			new Reference('output.node.printer.json.step_text_painter'),
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference('output.node.printer.json.path'),
//			new Reference(ExceptionExtension::PRESENTER_ID),
//			8
		]);
		$container->setDefinition('output.node.printer.json.example_step', $definition);
	}

	/**
	 * Loads hook printers.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadHookPrinters(ContainerBuilder $container)
	{
		$definition = new Definition(JsonSetupPrinter::class, [
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference(ExceptionExtension::PRESENTER_ID),
//			0,
//			true,
//			true
		]);
		$container->setDefinition('output.node.printer.json.suite_setup', $definition);

		$definition = new Definition(JsonSetupPrinter::class, [
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference(ExceptionExtension::PRESENTER_ID),
//			0,
//			false,
//			true
		]);
		$container->setDefinition('output.node.printer.json.feature_setup', $definition);

		$definition = new Definition(JsonSetupPrinter::class, [
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference(ExceptionExtension::PRESENTER_ID),
//			2
		]);
		$container->setDefinition('output.node.printer.json.scenario_setup', $definition);

		$definition = new Definition(JsonSetupPrinter::class, [
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference(ExceptionExtension::PRESENTER_ID),
//			4
		]);
		$container->setDefinition('output.node.printer.json.step_setup', $definition);

		$definition = new Definition(JsonSetupPrinter::class, [
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference(ExceptionExtension::PRESENTER_ID),
//			8
		]);
		$container->setDefinition('output.node.printer.json.example_step_setup', $definition);

		$definition = new Definition(JsonSetupPrinter::class, array(
//			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
//			new Reference(ExceptionExtension::PRESENTER_ID),
//			6
		));
		$container->setDefinition('output.node.printer.json.example_setup', $definition);
	}

	/**
	 * Loads statistics printer.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadStatisticsPrinter(ContainerBuilder $container)
	{
		$definition = new Definition(CounterPrinter::class, [
			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
			new Reference(TranslatorExtension::TRANSLATOR_ID),
		]);
		$container->setDefinition('output.node.printer.counter', $definition);

		$definition = new Definition(ListPrinter::class, [
			new Reference(self::RESULT_TO_STRING_CONVERTER_ID),
			new Reference(ExceptionExtension::PRESENTER_ID),
			new Reference(TranslatorExtension::TRANSLATOR_ID),
			'%paths.base%'
		]);
		$container->setDefinition('output.node.printer.list', $definition);

		$definition = new Definition(JsonStatisticsPrinter::class, [
			new Reference('output.node.printer.counter'),
			new Reference('output.node.printer.list')
		]);
		$container->setDefinition('output.node.printer.json.statistics', $definition);
	}

	/**
	 * Loads printer helpers.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadPrinterHelpers(ContainerBuilder $container)
	{
		$definition = new Definition(StepTextPainter::class, [
			new Reference(DefinitionExtension::PATTERN_TRANSFORMER_ID),
			new Reference(self::RESULT_TO_STRING_CONVERTER_ID)
		]);
		$container->setDefinition('output.node.printer.json.step_text_painter', $definition);

		$definition = new Definition(ResultToStringConverter::class);
		$container->setDefinition(self::RESULT_TO_STRING_CONVERTER_ID, $definition);
	}

	/**
	 * Loads formatter itself.
	 *
	 * @param ContainerBuilder $container
	 */
	protected function loadFormatter(ContainerBuilder $container)
	{
		$definition = new Definition(TotalStatistics::class);
		$container->setDefinition('output.json.statistics', $definition);

		$definition = new Definition(NodeEventListeningFormatter::class, [
			'json',
			'Outputs in json.',
			[
				'timer' => true,
			],
			$this->createOutputPrinterDefinition(),
			new Definition(ChainEventListener::class, [
				[
					new Reference(self::ROOT_LISTENER_ID),
					new Definition(ScenarioStatsListener::class, [
						new Reference('output.json.statistics')
					]),
					new Definition(StepStatsListener::class, [
						new Reference('output.json.statistics'),
						new Reference(ExceptionExtension::PRESENTER_ID)
					]),
					new Definition(HookStatsListener::class, [
						new Reference('output.json.statistics'),
						new Reference(ExceptionExtension::PRESENTER_ID)
					]),
				],
			]),
		]);
		$definition->addTag(OutputExtension::FORMATTER_TAG, ['priority' => 100]);
		$container->setDefinition(OutputExtension::FORMATTER_TAG . '.json', $definition);
	}

	protected function createOutputPrinterDefinition()
	{
		return new Definition(JsonOutputPrinter::class, [
			new Definition(FilesystemOutputFactory::class),
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
		return new Definition(FirstBackgroundFiresFirstListener::class, [
			new Definition(OnlyFirstBackgroundFiresListener::class, [
				$listener
			])
		]);
	}

	/**
	 * Creates contextual proxy listener.
	 *
	 * @param string       $beforeEventName
	 * @param string       $afterEventName
	 * @param Definition[] $listeners
	 *
	 * @return Definition
	 */
	protected function proxySiblingEvents($beforeEventName, $afterEventName, array $listeners)
	{
		return new Definition(FireOnlySiblingsListener::class,
			[
				$beforeEventName,
				$afterEventName,
				new Definition('Behat\Testwork\Output\Node\EventListener\ChainEventListener', array($listeners))
			]
		);
	}

	/**
	 * Creates contextual proxy listener.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @param mixed  $listener
	 *
	 * @return Definition
	 */
	protected function proxyEventsIfParameterIsSet($name, $value, Definition $listener)
	{
		return new Definition(FireOnlyIfFormatterParameterListener::class,
			[$name, $value, $listener]
		);
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