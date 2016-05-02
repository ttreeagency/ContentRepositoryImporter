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
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Booting\Scripts;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\EventLog\Domain\Service\EventEmittingService;

/**
 * Import Command Controller
 *
 * @Flow\Scope("singleton")
 */
class ImportCommandController extends CommandController
{
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
     * @var EventEmittingService
     */
    protected $eventEmittingService;

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
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Remove all import event from the event log
     */
    public function flushEventLogCommand()
    {
        $this->eventLogRepository->removeAll();
    }

    /**
     * Batch process of the given preset
     *
     * @param string $preset
     * @param string $parts
     */
    public function batchCommand($preset, $parts = null)
    {
        $this->importService->start();
        $this->startTime = microtime(true);
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

            if ($partSetting->getBatchSize() && $partSetting->isDebug() === false) {
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
        $this->outputLine('Import finished.');
        $this->outputLine(sprintf('  Started   %s', $import->getStart()->format(DATE_RFC2822)));
        $this->outputLine(sprintf('  Finished  %s', $import->getEnd()->format(DATE_RFC2822)));
        $this->outputLine(sprintf('  Runtime   %d seconds', $import->getElapsedTime()));
        $this->outputLine();
        $this->outputLine('See log for more details and possible errors.');
    }

    /**
     * @param PresetPartDefinition $partSetting
     * @return integer
     */
    protected function executeCommand(PresetPartDefinition $partSetting)
    {
        try {
            $this->importService->addEvent(sprintf('%s:Started', $partSetting->getEventType()), null, $partSetting->getCommandArguments());
            $this->importService->persistEntities();

            $startTime = microtime(true);

            ++$this->batchCounter;
            ob_start();
            $status = Scripts::executeCommand('ttree.contentrepositoryimporter:import:executebatch', $this->getFlowSettings(), true, $partSetting->getCommandArguments());
            if ($status !== true) {
                throw new Exception('Sub command failed', 1426767159);
            }
            $count = (integer)ob_get_clean();
            if ($count < 1) {
                return 0;
            }

            $elapsedTime = (microtime(true) - $startTime) * 1000;
            $this->elapsedTime += $elapsedTime;
            $this->outputLine('  #%d %d records in %dms, %d ms per record, %d ms per batch (avg)', [$partSetting->getCurrentBatch(), $count, $elapsedTime, $elapsedTime / $count, $this->elapsedTime / $this->batchCounter]);
            $this->importService->addEvent(sprintf('%s:Ended', $partSetting->getEventType()), null, $partSetting->getCommandArguments());
            $this->importService->persistEntities();
            return $count;
        } catch (\Exception $exception) {
            $this->logger->logException($exception);
            $this->outputLine("Error, please check your logs ...", [$partSetting->getLabel()]);
            $this->importService->addEvent(sprintf('%s:Failed', $partSetting->getEventType()), null, $partSetting->getCommandArguments());
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
    public function executeBatchCommand($presetName, $partName, $dataProviderClassName, $importerClassName, $currentImportIdentifier, $offset = null, $batchSize = null)
    {
        try {
            $dataProviderOptions = Arrays::getValueByPath($this->settings, implode('.', ['presets', $presetName, $partName, 'dataProviderOptions']));

            /** @var DataProvider $dataProvider */
            $dataProvider = $dataProviderClassName::create(is_array($dataProviderOptions) ? $dataProviderOptions : [], $offset, $batchSize);

            $importerOptions = Arrays::getValueByPath($this->settings, ['presets', $presetName, $partName, 'importerOptions']);

            /** @var Importer $importer */
            $importer = $this->objectManager->get($importerClassName, is_array($importerOptions) ? $importerOptions : [], $currentImportIdentifier);
            $importer->getImportService()->addEventMessage(sprintf('%s:Batch:Started', $importerClassName), sprintf('%s batch started (%s)', $importerClassName, $dataProviderClassName));
            $importer->initialize($dataProvider);
            $importer->process();
            $importer->getImportService()->addEventMessage(sprintf('%s:Batch:Ended', $importerClassName), sprintf('%s batch ended (%s)', $importerClassName, $dataProviderClassName));
            $this->output($importer->getProcessedRecords());
        } catch (\Exception $exception) {
            $this->logger->logException($exception);
            $this->quit(1);
        }
    }

    /**
     * @return array
     */
    protected function getFlowSettings()
    {
        return $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');
    }
}
