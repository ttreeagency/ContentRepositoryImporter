<?php
namespace Ttree\ContentRepositoryImporter\Command;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\DataProvider\DataProvider;
use Ttree\ContentRepositoryImporter\Domain\Model\PresetPartDefinition;
use Ttree\ContentRepositoryImporter\Domain\Repository\EventRepository;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Ttree\ContentRepositoryImporter\Importer\Importer;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Booting\Scripts;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * Import Command Controller
 *
 * @Flow\Scope("singleton")
 */
class ImportCommandController extends CommandController {

	/**
	 * @var VariableFrontend
	 */
	protected $cache;

	/**
	 * @Flow\Inject
	 * @var ImportService
	 */
	protected $importService;

	/**
	 * @Flow\Inject
	 * @var EventRepository
	 */
	protected $eventLogRepository;

	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $logger;

	/**
	 * @Flow\Inject
	 * @var array
	 */
	protected $settings;

	/**
	 * @var float
	 */
	protected $startTime;

	/**
	 * @var integer
	 */
	protected $elapsedTime;

	/**
	 * @var integer
	 */
	protected $batchCounter = 0;

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Reset processed node cache
	 */
	public function flushCacheCommand() {
		$this->cache->flush();
	}

	/**
	 * Remove all import event from the event log
	 */
	public function flushEventLogCommand() {
		$this->eventLogRepository->removeAll();
	}

	/**
	 * Batch process of the given preset
	 *
	 * @param string $preset
	 * @param string $parts
	 */
	public function batchCommand($preset, $parts = NULL) {
		$this->importService->start();
		$this->startTime = microtime(TRUE);
		$parts = Arrays::trimExplode(',', $parts);
		$this->outputLine('Start import ...');
		$presetSettings = Arrays::getValueByPath($this->settings, array('presets', $preset));
		if (!is_array($presetSettings)) {
			$this->outputLine(sprintf('Preset "%s" not found ...', $preset));
			$this->quit(1);
		}
		array_walk($presetSettings, function ($partSetting, $partName) use ($preset, $parts) {
			$this->elapsedTime = 0;
			$this->batchCounter = 0;
			$this->outputLine();
			$this->outputFormatted(sprintf('<b>%s</b>', $partSetting['label']));

			$partSetting['__currentPresetName'] = $preset;
			$partSetting['__currentPartName'] = $partName;

			$partSetting = new PresetPartDefinition($partSetting, $this->importService->getCurrentImportIdentifier());
			if ($parts !== array() && !in_array($partName, $parts)) {
				$this->outputLine('Skipped');
				return;
			}
			if ($partSetting->getBatchSize()) {
				while (($count = $this->executeCommand($partSetting)) > 0) {
					$partSetting->nextBatch();
				}
			} else {
				$this->executeCommand($partSetting);
			}
		});

		$this->importService->stop();
		$import = $this->importService->getLastImport();
		$this->outputLine();
		$this->outputLine('Import finished');
		$this->outputLine(sprintf('  Started   %s', $import->getStart()->format(DATE_RFC2822)));
		$this->outputLine(sprintf('  Finished  %s', $import->getEnd()->format(DATE_RFC2822)));
		$this->outputLine(sprintf('  Runtime   %d seconds', $import->getElapsedTime()));
	}

	/**
	 * @param PresetPartDefinition $partSetting
	 * @return integer
	 */
	protected function executeCommand(PresetPartDefinition $partSetting) {
		try {
			$this->importService->addEvent(sprintf('%s:Started', $partSetting->getEventType()), NULL, $partSetting->getCommandArguments());
			$this->importService->persisteEntities();

			$startTime = microtime(TRUE);

			++$this->batchCounter;
			ob_start();
			$status = Scripts::executeCommand('ttree.contentrepositoryimporter:import:executebatch', $this->getFlowSettings(), TRUE, $partSetting->getCommandArguments());
			if ($status !== TRUE) {
				throw new Exception('Sub command failed', 1426767159);
			}
			$count = (integer)ob_get_clean();
			if ($count < 1) {
				return 0;
			}

			$elapsedTime = (microtime(TRUE) - $startTime) * 1000;
			$this->elapsedTime += $elapsedTime;
			$this->outputLine('  #%d %d records in %dms, %d ms per record, %d ms per batch (avg)', [$partSetting->getCurrentBatch(), $count, $elapsedTime, $elapsedTime / $count, $this->elapsedTime / $this->batchCounter]);
			$this->importService->addEvent(sprintf('%s:Ended', $partSetting->getEventType()), NULL, $partSetting->getCommandArguments());
			$this->importService->persisteEntities();
			return $count;
		} catch (\Exception $exception) {
			$this->logger->logException($exception);
			$this->outputLine("Error, please check your logs ...", [$partSetting->getLabel()]);
			$this->importService->addEvent(sprintf('%s:Failed', $partSetting->getEventType()), NULL, $partSetting->getCommandArguments());
			$this->quit(1);
		}
	}

	/**
	 * @param string $presetName
	 * @param string $partName
	 * @param string $dataProviderClassName
	 * @param string $importerClassName
	 * @param string $currentImportIdentifier
	 * @param integer $offset
	 * @param integer $batchSize
	 * @return integer
	 * @Flow\Internal
	 */
	public function executeBatchCommand($presetName, $partName, $dataProviderClassName, $importerClassName, $currentImportIdentifier, $offset = NULL, $batchSize = NULL) {
		try {
			$dataProviderOptions = Arrays::getValueByPath($this->settings, implode('.', ['presets', $presetName, $partName, 'dataProviderOptions']));

			/** @var DataProvider $dataProvider */
			$dataProvider = $dataProviderClassName::create(is_array($dataProviderOptions) ? $dataProviderOptions : [], $offset, $batchSize);

			$importerOptions = Arrays::getValueByPath($this->settings, ['presets', $presetName, $partName, 'importerOptions']);

			/** @var Importer $importer */
			$importer = $this->objectManager->get($importerClassName, is_array($importerOptions) ? $importerOptions : [], $currentImportIdentifier);
			$importer->initialize($dataProvider);
			$importer->process();

			$this->output($importer->getProcessedRecords());
		} catch (\Exception $exception) {
			$this->logger->logException($exception);
			$this->quit(1);
		}
	}

	/**
	 * @return array
	 */
	protected function getFlowSettings() {
		return $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');
	}

}