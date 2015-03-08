<?php
namespace Ttree\ContentRepositoryImporter\Command;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter".   *
 *                                                                                    */

use Ttree\ContentRepositoryImporter\DataProvider\DataProvider;
use Ttree\ContentRepositoryImporter\Importer\Importer;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Booting\Scripts;
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
	 * @var array
	 */
	protected $settings;

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
	 * Batch process of the given preset
	 *
	 * @param string $preset
	 */
	public function batchCommand($preset) {
		$this->outputLine('Start import ...');
		$arguments = [ 'logPrefix' => Algorithms::generateRandomString(12) ];
		$presetSettings = Arrays::getValueByPath($this->settings, array('presets', $preset));
		if (!is_array($presetSettings)) {
			$this->outputLine(sprintf('Preset "%s" not found ...', $preset));
			$this->quit(1);
		}
		array_walk($presetSettings, function ($command) use ($arguments) {
			$arguments['dataProviderClassName'] = (string)$command['dataProviderClassName'];
			$arguments['importerClassName'] = (string)$command['importerClassName'];
			if (isset($command['batchSize'])) {
				$arguments['batchSize'] = (integer)$command['batchSize'];
				$arguments['offset'] = 0;
				while (($count = $this->executeCommand($command, $arguments)) > 0) {
					$arguments['offset'] += $count;
				}
			} else {
				$this->executeCommand($command, $arguments);
			}
		});

		$this->outputLine('Import finished');
	}

	/**
	 * @param array $command
	 * @param array $arguments
	 * @return integer
	 */
	protected function executeCommand(array $command, array $arguments) {
		$this->outputLine(sprintf(' - %s', $command['label']));
		ob_start();
		$status = Scripts::executeCommand('ttree.contentrepositoryimporter:import:executebatch', $this->getFlowSettings(), TRUE, $arguments);
		$count = (integer)ob_get_clean();
		$this->outputLine('   Processed record(s): ' . $count);
		if ($status !== TRUE) {
			$this->outputLine("Command '%s' return an error", array($command));
			$this->quit(1);
		}
		return $count;
	}

	/**
	 * @param string $dataProviderClassName
	 * @param string $importerClassName
	 * @param string $logPrefix
	 * @param integer $offset
	 * @param integer $batchSize
	 * @return integer
	 * @Flow\Internal
	 */
	public function executeBatchCommand($dataProviderClassName, $importerClassName, $logPrefix, $offset = NULL, $batchSize = NULL) {
		/** @var DataProvider $dataProvider */
		$dataProvider = $this->objectManager->get($dataProviderClassName, $offset, $batchSize);

		/** @var Importer $importer */
		$importer = $this->objectManager->get($importerClassName);
		$importer->setLogPrefix($logPrefix);
		$importer->import($dataProvider);

		$this->output($dataProvider->getCount());
	}

	/**
	 * @return array
	 */
	protected function getFlowSettings() {
		return $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');
	}

}