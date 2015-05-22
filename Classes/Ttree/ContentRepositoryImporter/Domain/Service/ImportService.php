<?php
namespace Ttree\ContentRepositoryImporter\Domain\Service;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Doctrine\ORM\Mapping as ORM;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Model\Import;
use Ttree\ContentRepositoryImporter\Domain\Model\RecordMapping;
use Ttree\ContentRepositoryImporter\Domain\Repository\ImportRepository;
use Ttree\ContentRepositoryImporter\Domain\Repository\RecordMappingRepository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class ImportService  {

	/**
	 * @Flow\Inject
	 * @var ImportRepository
	 */
	protected $importRepository;

	/**
	 * @Flow\Inject
	 * @var RecordMappingRepository
	 */
	protected $recordMappingRepository;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Import
	 */
	protected $currentImport;

	/**
	 * @var Import
	 */
	protected $lastImport;

	/**
	 * @param string $identifier
	 * @throws Exception
	 */
	public function resume($identifier) {
		if ($this->currentImport instanceof Import) {
			throw new Exception('Unable to resume, please stop the current import first', 1426638560);
		}
		$this->currentImport = $this->importRepository->findByIdentifier($identifier);
	}

	/**
	 * Start a new Import
	 */
	public function start() {
		if ($this->currentImport instanceof Import) {
			throw new Exception('Unable to start a new import, please stop the current import first', 1426638560);
		}
		$this->currentImport = new Import();
		$this->importRepository->add($this->currentImport);
	}

	/**
	 * Stop and store the current Import
	 */
	public function stop() {
		if (!$this->currentImport instanceof Import) {
			throw new Exception('Unable to stop the current import, please start an import first', 1426638563);
		}
		$this->currentImport->end();
		$this->importRepository->update($this->currentImport);
		$this->lastImport = clone $this->currentImport;
		unset($this->currentImport);
	}

	/**
	 * @param string $importerClassName
	 * @param string $externalIdentifier
	 * @param string $externalRelativeUri
	 * @param string $nodeIdentifier
	 * @param string $nodePath
	 */
	public function addOrUpdateRecordMapping($importerClassName, $externalIdentifier, $externalRelativeUri, $nodeIdentifier, $nodePath) {
		$recordMapping = $this->recordMappingRepository->findOneByImporterClassNameAndExternalIdentifier($importerClassName, $externalIdentifier);
		if ($recordMapping === NULL) {
			$recordMapping = new RecordMapping($importerClassName, $externalIdentifier, $externalRelativeUri, $nodeIdentifier, $nodePath);
			$this->recordMappingRepository->add($recordMapping);
		} else {
			$recordMapping->setExternalRelativeUri($externalRelativeUri);
			$recordMapping->setNodeIdentifier($nodeIdentifier);
			$recordMapping->setNodePath($nodePath);
			$this->recordMappingRepository->update($recordMapping);
		}

		$this->recordMappingRepository->persistEntities();
		$this->addEvent(sprintf('%s:Record:Ended', substr($importerClassName, strrpos($importerClassName, '\\') + 1)), $externalIdentifier, [
			'importerClassName' => $importerClassName,
			'externalIdentifier' => $externalIdentifier,
			'externalRelativeUri' => $externalRelativeUri,
			'nodeIdentifier' => $nodeIdentifier,
			'nodePath' => $nodePath
		]);
	}

	/**
	 * @param string $eventType
	 * @param string $externalIdentifier
	 * @param array $data
	 * @param Event $parentEvent
	 * @return Event
	 * @throws Exception
	 */
	public function addEvent($eventType, $externalIdentifier = NULL, array $data = NULL, Event $parentEvent = NULL) {
		if (!$this->currentImport instanceof Import) {
			throw new Exception('Unable to add an event, please start an import first', 1426638562);
		}
		$event = $this->currentImport->addEvent($eventType, $externalIdentifier, $data ?: array(), $parentEvent);

		return $event;
	}

	/**
	 * @param string $eventType
	 * @param string $message
	 * @param int $severity
	 * @param Event $parentEvent
	 * @return Event
	 * @throws Exception
	 */
	public function addEventMessage($eventType, $message, $severity = LOG_INFO, Event $parentEvent = NULL) {
		if (!$this->currentImport instanceof Import) {
			throw new Exception('Unable to add an event, please start an import first', 1426638563);
		}
		$event = $this->currentImport->addEvent($eventType, NULL, [
			'__message' => $message,
			'__severity' => $severity
		], $parentEvent);

		return $event;
	}

	/**
	 * Persist all pending entities
	 */
	public function persisteEntities() {
		$this->importRepository->persistEntities();
	}

	/**
	 * @return Import
	 * @throws Exception
	 */
	public function getCurrentImport() {
		if (!$this->currentImport instanceof Import) {
			throw new Exception('Unable to get current import, please start an import first', 1426638561);
		}
		return $this->currentImport;
	}

	/**
	 * @return Import
	 * @throws Exception
	 */
	public function getLastImport() {
		if (!$this->lastImport instanceof Import) {
			throw new Exception('Last import is not set', 1426638561);
		}
		return $this->lastImport;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getCurrentImportIdentifier() {
		if (!$this->currentImport instanceof Import) {
			throw new Exception('Unable to get import identifier, please start an import first', 1426638561);
		}
		return $this->persistenceManager->getIdentifierByObject($this->currentImport);
	}

}