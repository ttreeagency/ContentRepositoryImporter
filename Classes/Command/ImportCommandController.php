<?php
namespace Ttree\ContentRepositoryImporter\Command;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Ttree\ContentRepositoryImporter\DataProvider\DataProviderInterface;
use Ttree\ContentRepositoryImporter\Domain\Model\PresetPartDefinition;
use Ttree\ContentRepositoryImporter\Domain\Repository\EventRepository;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Ttree\ContentRepositoryImporter\Exception\ImportAlreadyExecutedException;
use Ttree\ContentRepositoryImporter\Importer\AbstractImporter;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Exception;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Arrays;
use Neos\Neos\EventLog\Domain\Service\EventEmittingService;

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
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array
     */
    protected $flowSettings;

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
     * Run batch import
     *
     * This executes a batch import as configured in the settings for the specified preset. Optionally the "parts" can
     * be specified, separated by comma ",".
     *
     * Presets and parts need to be configured via settings first. Refer to the documentation for possible options and
     * example configurations.
     *
     * You may optionally specify an external import identifier which will be stored as meta data with the import run.
     * This identifier is used for checking if an import of the given data set (in general) has been executed earlier.
     * The external import identifier therefore does globally what the external record identifier does on a per record
     * basis.
     *
     * If an external import identifier was specified and an import using that identifier has been executed earlier,
     * this command will stop with a corresponding message. You can force running such an import by specifying the
     * --force flag.
     *
     * @param string $preset Name of the preset which holds the configuration for the import
     * @param string $parts Optional comma separated names of parts. If no parts are specified, all parts will be imported.
     * @param integer $batchSize Number of records to import at a time. If not specified, the batch size defined in the preset will be used.
     * @param string $externalImportIdentifier External identifier which is used for checking if an import of the same data has already been executed earlier.
     * @param boolean $force If set, an import will even be executed if it ran earlier with the same external import identifier.
     * @return void
     */
    public function batchCommand($preset, $parts = null, $batchSize = null, $externalImportIdentifier = null, $force = false)
    {
        try {
            $this->importService->start($externalImportIdentifier, $force);
        } catch (ImportAlreadyExecutedException $e) {
            $this->outputLine($e->getMessage());
            $this->outputLine('Import skipped. You can force running this import again by specifying --force.');
            $this->quit(1);
        }

        $this->startTime = microtime(true);
        $parts = Arrays::trimExplode(',', $parts);

        $this->outputLine('Start import ...');
        $presetSettings = Arrays::getValueByPath($this->settings, array('presets', $preset));
        if (!is_array($presetSettings)) {
            $this->outputLine(sprintf('Preset "%s" not found ...', $preset));
            $this->quit(1);
        }

        $this->checkForPartsSettingsOrQuit($presetSettings, $preset);


        array_walk($presetSettings['parts'], function ($partSetting, $partName) use ($preset, $parts, $batchSize) {
            $this->elapsedTime = 0;
            $this->batchCounter = 0;
            $this->outputLine();
            $this->outputFormatted(sprintf('<b>%s</b>', $partSetting['label']));

            $partSetting['__currentPresetName'] = $preset;
            $partSetting['__currentPartName'] = $partName;
            if ($batchSize !== null) {
                $partSetting['batchSize'] = $batchSize;
            }

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
        $this->outputLine(sprintf('  Started   %s', $import->getStartTime()->format(DATE_RFC2822)));
        $this->outputLine(sprintf('  Finished  %s', $import->getEndTime()->format(DATE_RFC2822)));
        $this->outputLine(sprintf('  Runtime   %d seconds', $import->getElapsedTime()));
        $this->outputLine();
        $this->outputLine('See log for more details and possible errors.');
    }

    /**
     * Execute a sub process which imports a batch as specified by the part definition.
     *
     * @param PresetPartDefinition $partSetting
     * @return integer The number of records which have been imported
     */
    protected function executeCommand(PresetPartDefinition $partSetting)
    {
        try {
            $this->importService->addEvent(sprintf('%s:Started', $partSetting->getEventType()), null, $partSetting->getCommandArguments());
            $this->importService->persistEntities();

            $startTime = microtime(true);

            ++$this->batchCounter;
            ob_start();
            $status = Scripts::executeCommand('ttree.contentrepositoryimporter:import:executebatch', $this->flowSettings, true, $partSetting->getCommandArguments());
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
     * Import a single batch
     *
     * This internal command is called by executeCommand() and runs an isolated import for a batch as specified by
     * the command's arguments.
     *
     * @param string $presetName
     * @param string $partName
     * @param string $dataProviderClassName
     * @param string $importerClassName
     * @param string $currentImportIdentifier
     * @param integer $offset
     * @param integer $batchSize
     * @return void
     * @Flow\Internal
     */
    public function executeBatchCommand($presetName, $partName, $dataProviderClassName, $importerClassName, $currentImportIdentifier, $offset = null, $batchSize = null)
    {
        try {
            $dataProviderOptions = Arrays::getValueByPath($this->settings, implode('.', ['presets', $presetName, 'parts', $partName, 'dataProviderOptions']));

            /** @var DataProviderInterface $dataProvider */
            $dataProvider = $dataProviderClassName::create(is_array($dataProviderOptions) ? $dataProviderOptions : [], $offset, $batchSize);

            $importerOptions = Arrays::getValueByPath($this->settings, ['presets', $presetName, 'parts', $partName, 'importerOptions']);

            /** @var AbstractImporter $importer */
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
     * Clean up event log
     *
     * This command removes all Neos event log entries caused by the importer.
     *
     * @return void
     */
    public function flushEventLogCommand()
    {
        $this->eventLogRepository->removeAll();
    }

    /**
     * Checks if the preset settings contain a "parts" segment and quits if it does not.
     *
     * @param array $presetSettings
     * @param string $preset
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    protected function checkForPartsSettingsOrQuit(array $presetSettings, $preset)
    {
        if (!isset($presetSettings['parts'])) {
            $this->outputLine('<b>No "parts" array found for import preset "%s</b>".', [ $preset ]);
            $this->outputLine();
            $this->outputLine('Please note that the settings structure has changed slightly. Instead of just defining');
            $this->outputLine('parts as a sub-array of the respective preset, you now need to define them in a sub-array');
            $this->outputLine('called "parts".');
            $this->outputLine('');
            $this->outputLine('Ttree:');
            $this->outputLine('  ContentRepositoryImporter:');
            $this->outputLine('    presets:');
            $this->outputLine("      '$preset':");
            $this->outputLine("        parts:");
            if (is_array($presetSettings) && count($presetSettings) > 0) {
                $firstPart = array_keys($presetSettings)[0];
                $this->outputLine("          '$firstPart':");
            }
            $this->outputLine("          ...");
            $this->quit(1);
        }
    }

}
