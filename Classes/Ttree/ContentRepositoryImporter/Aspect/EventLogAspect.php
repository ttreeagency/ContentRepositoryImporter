<?php
namespace Ttree\ContentRepositoryImporter\Aspect;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Ttree\ContentRepositoryImporter\Importer\ImporterInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\AOP\JoinPointInterface;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * Aspect to automatically handle EventLog in Importer object
 *
 * @Flow\Aspect
 */
class EventLogAspect {

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
	 * @var SystemLoggerInterface
	 */
	protected $logger;

	/**
	 * Add record started event
	 *
	 * @Flow\Before("within(Ttree\ContentRepositoryImporter\Importer\ImporterInterface) && method(.*->processRecord())")
	 * @param JoinPointInterface $joinPoint
	 */
	public function addRecordStartedEvent(JoinPointInterface $joinPoint) {
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
	public function flushEvents(JoinPointInterface $joinPoint) {
		try {
			$this->importService->persisteEntities();
			$this->nodeDataRepository->persistEntities();
		} catch (\Exception $exception) {
			$this->logger->logException($exception);
		}
	}

	/**
	 * Get the Importer and DataProvider class name
	 *
	 * @param ImporterInterface $importer
	 * @return array
	 */
	protected function getImporterClassNames(ImporterInterface $importer) {
		$importerClassName = get_class($importer);
		$importerClassName = substr($importerClassName, strrpos($importerClassName, '\\') + 1);

		$dataProvider = $importer->getDataProvider();
		$dataProviderClassName = get_class($dataProvider);
		$dataProviderClassName = substr($dataProviderClassName, strrpos($dataProviderClassName, '\\') + 1);

		return [$importerClassName, $dataProviderClassName];
	}

}