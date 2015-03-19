<?php
namespace Ttree\ContentRepositoryImporter\Domain\Service;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Doctrine\ORM\Mapping as ORM;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Model\Import;
use Ttree\ContentRepositoryImporter\Domain\Repository\ImportRepository;
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
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Import
	 */
	protected $currentImport;

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
		unset($this->currentImport);
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
	 * Persist all pending events
	 */
	public function persisteEvents() {
		$this->importRepository->persistEntities();
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