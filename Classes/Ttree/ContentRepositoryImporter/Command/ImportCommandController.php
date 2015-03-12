<?php
namespace Ttree\ContentRepositoryImporter\Command;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\DataProvider\DataProvider;
use Ttree\ContentRepositoryImporter\Domain\Model\PresetPartDefinition;
use Ttree\ContentRepositoryImporter\Importer\Importer;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Booting\Scripts;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\Algorithms;
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
	 * @var VariableFrontend
	 */
	protected $downloadCache;

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
		$this->downloadCache->flush();
	}

	/**
	 * Batch process of the given preset
	 *
	 * @param string $preset
	 * @param string $parts
	 */
	public function batchCommand($preset, $parts = NULL) {
		$this->startTime = microtime(TRUE);
		$parts = Arrays::trimExplode(',', $parts);
		$this->outputLine('Start import ...');
		$logPrefix = Algorithms::generateRandomString(12);
		$presetSettings = Arrays::getValueByPath($this->settings, array('presets', $preset));
		if (!is_array($presetSettings)) {
			$this->outputLine(sprintf('Preset "%s" not found ...', $preset));
			$this->quit(1);
		}
		array_walk($presetSettings, function ($partSetting, $partName) use ($preset, $logPrefix, $parts) {
			$this->elapsedTime = 0;
			$this->batchCounter = 0;
			$this->outputLine();
			$this->outputFormatted(sprintf('<b>%s</b>', $partSetting['label']));

			$partSetting['__currentPresetName'] = $preset;
			$partSetting['__currentPartName'] = $partName;

			$partSetting = new PresetPartDefinition($partSetting, $logPrefix);
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

		$this->outputLine();
		$this->outputLine('Import finished');
	}

	/**
	 * @param PresetPartDefinition $partSetting
	 * @return integer
	 */
	protected function executeCommand(PresetPartDefinition $partSetting) {
		$startTime = microtime(TRUE);
		ob_start();
		$status = Scripts::executeCommand('ttree.contentrepositoryimporter:import:executebatch', $this->getFlowSettings(), TRUE, $partSetting->getCommandArguments());
		$count = (integer)ob_get_clean();
		$elapsedTime = (microtime(TRUE) - $startTime) * 1000;
		$this->elapsedTime += $elapsedTime;
		++$this->batchCounter;
		if ($count > 0) {
			$this->outputLine('  #%d %d records in %dms, %d ms per record, %d ms per batch (avg)', [$partSetting->getCurrentBatch(), $count, $elapsedTime, $elapsedTime / $count, $this->elapsedTime / $this->batchCounter]);
		}
		if ($status !== TRUE) {
			$this->outputLine("Command '%s' return an error", [ $partSetting->getLabel() ] );
			$this->quit(1);
		}
		return $count;
	}

	/**
	 * @param string $presetName
	 * @param string $partName
	 * @param string $dataProviderClassName
	 * @param string $importerClassName
	 * @param string $logPrefix
	 * @param integer $offset
	 * @param integer $batchSize
	 * @return integer
	 * @Flow\Internal
	 */
	public function executeBatchCommand($presetName, $partName, $dataProviderClassName, $importerClassName, $logPrefix, $offset = NULL, $batchSize = NULL) {
		try {
			$dataProviderOptions = Arrays::getValueByPath($this->settings, implode('.', ['presets', $presetName, $partName, 'dataProviderOptions']));
			/** @var DataProvider $dataProvider */
			$dataProvider = $dataProviderClassName::create(is_array($dataProviderOptions) ? $dataProviderOptions : [], $offset, $batchSize);

			$importerOptions = Arrays::getValueByPath($this->settings, ['presets', $presetName, $partName, 'importerOptions']);
			/** @var Importer $importer */
			$importer = $this->objectManager->get($importerClassName, is_array($importerOptions) ? $importerOptions : []);
			$importer->setLogPrefix($logPrefix);
			$importer->import($dataProvider);

			$this->output($dataProvider->getCount());
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