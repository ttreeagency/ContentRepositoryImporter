<?php
namespace Ttree\ContentRepositoryImporter\Aspect;

use Exception;
use Neos\Flow\Log\ThrowableStorageInterface;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Ttree\ContentRepositoryImporter\Importer\ImporterInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\Flow\Log\Utility\LogEnvironment;

/**
 * Aspect to automatically handle EventLog in Importer object
 *
 * @Flow\Aspect
 */
class EventLogAspect
{
    /**
     * @Flow\Inject
     * @var ImportService
     */
    protected $importService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * Add record started event
     *
     * @Flow\Before("within(Ttree\ContentRepositoryImporter\Importer\ImporterInterface) && method(.*->processRecord())")
     * @param JoinPointInterface $joinPoint
     * @throws \Neos\Flow\Exception
     */
    public function addRecordStartedEvent(JoinPointInterface $joinPoint)
    {
        $data = $joinPoint->getMethodArgument('data');
        $externalIdentifier = Arrays::getValueByPath($data, '__externalIdentifier');
        $title = Arrays::getValueByPath($data, '__label');
        /** @var ImporterInterface $importer */
        $importer = $joinPoint->getProxy();
        list($importerClassName) = $this->getImporterClassNames($importer);

        $data['__message'] = sprintf('%s: "%s" (%s)', $importerClassName, $title ?: '-- No label --', $externalIdentifier);
        $event = $importer->getImportService()->addEvent(sprintf('%s:Record:Started', $importerClassName), $externalIdentifier, $data);
        $importer->setCurrentEvent($event);
    }

    /**
     * Flush all event after the ImporterInterface::processRecord()
     *
     * As an import batch can be a long running process, this ensure that the EventLog is flushed after each record processing
     *
     * @Flow\After("within(Ttree\ContentRepositoryImporter\Importer\ImporterInterface) && method(.*->processRecord())")
     * @param JoinPointInterface $joinPoint
     */
    public function flushEvents(JoinPointInterface $joinPoint)
    {
        try {
            $this->importService->persistEntities();
            $this->nodeDataRepository->persistEntities();
        } catch (Exception $exception) {
            $logMessage = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
        }
    }

    /**
     * Get the Importer and DataProvider class name
     *
     * @param ImporterInterface $importer
     * @return array
     */
    protected function getImporterClassNames(ImporterInterface $importer)
    {
        $importerClassName = get_class($importer);
        $importerClassName = substr($importerClassName, strrpos($importerClassName, '\\') + 1);

        $dataProvider = $importer->getDataProvider();
        $dataProviderClassName = get_class($dataProvider);
        $dataProviderClassName = substr($dataProviderClassName, strrpos($dataProviderClassName, '\\') + 1);

        return [$importerClassName, $dataProviderClassName];
    }
}
