<?php
namespace Ttree\ContentRepositoryImporter\Aspect;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Ttree\ContentRepositoryImporter\Importer\ImporterInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\AOP\JoinPointInterface;
use TYPO3\Flow\Utility\Arrays;

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
	 * Add batch started event
	 *
	 * @Flow\Before("within(Ttree\ContentRepositoryImporter\Importer\ImporterInterface) && method(.*->process())")
	 * @param JoinPointInterface $joinPoint
	 */
	public function addBatchStartedEvent(JoinPointInterface $joinPoint) {
		/** @var ImporterInterface $importer */
		$importer = $joinPoint->getProxy();
		list($importerClassName, $dataProviderClassName) = $this->getImporterClassNames($importer);

		$importer->getImportService()->addEventMessage(sprintf('%s:Batch:Started', $importerClassName), sprintf('%s batch started via %s', $importerClassName, $dataProviderClassName));
	}

	/**
	 * Add batch ended event
	 *
	 * @Flow\After("within(Ttree\ContentRepositoryImporter\Importer\ImporterInterface) && method(.*->process())")
	 * @param JoinPointInterface $joinPoint
	 */
	public function addBatchEndedEvent(JoinPointInterface $joinPoint) {
		/** @var ImporterInterface $importer */
		$importer = $joinPoint->getProxy();
		list($importerClassName, $dataProviderClassName) = $this->getImporterClassNames($importer);

		$importer->getImportService()->addEventMessage(sprintf('%s:Batch:Ended', $importerClassName), sprintf('%s batch ended via %s', $importerClassName, $dataProviderClassName));
	}

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
		$this->importService->persisteEvents();
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