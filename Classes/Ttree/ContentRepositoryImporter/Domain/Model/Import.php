<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Neos\EventLog\Domain\Service\EventEmittingService;

/**
 * @Flow\Entity
 */
class Import
{
    /**
     * @var \DateTime
     */
    protected $start;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $end;

    /**
     * @Flow\Inject
     * @var EventEmittingService
     */
    protected $eventEmittingService;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $logger;

    /**
     * @param integer $initializationCause
     */
    public function initializeObject($initializationCause)
    {
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            $this->start = new \DateTime();
            $this->addImportStartedEvent();
        }
    }

    /**
     * @param string $eventType
     * @param string $externalIdentifier
     * @param array $data
     * @param Event $parentEvent
     * @return Event
     * @throws \TYPO3\Neos\Exception
     */
    public function addEvent($eventType, $externalIdentifier = null, array $data = null, Event $parentEvent = null)
    {
        if (is_array($data) && isset($data['__message'])) {
            $message = $parentEvent ? sprintf('- %s', $data['__message']) : $data['__message'];
            $this->logger->log($message, isset($data['__severity']) ? (integer)$data['__severity'] : LOG_INFO);
        }
        $event = new Event($eventType, $data, null, $parentEvent);
        $event->setExternalIdentifier($externalIdentifier);
        try {
            $this->eventEmittingService->add($event);
        } catch (\Exception $exception) {
        }

        return $event;
    }

    /**
     * Add a Import.Started event in the EventLog
     */
    protected function addImportStartedEvent()
    {
        $event = new Event('Import.Started', array());
        try {
            $this->eventEmittingService->add($event);
        } catch (\Exception $exception) {
        }
    }

    /**
     * Add a Import.Ended event in the EventLog
     */
    protected function addImportEndedEvent()
    {
        try {
            $event = new Event('Import.Ended', array());
            $this->eventEmittingService->add($event);
        } catch (\Exception $exception) {
        }
    }

    /**
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @return integer
     */
    public function getElapsedTime()
    {
        return (integer) $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    /**
     * @throws Exception
     */
    public function end()
    {
        if ($this->end instanceof \DateTime) {
            throw new Exception('This import has ended earlier', 1426763297);
        }
        $this->end = new \DateTime();
        $this->addImportEndedEvent();
    }
}
