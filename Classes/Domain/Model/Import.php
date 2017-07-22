<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\EventLog\Domain\Service\EventEmittingService;

/**
 * @Flow\Entity
 */
class Import
{
    /**
     * @var \DateTime
     */
    protected $startTime;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $endTime;

    /**
     * @var string
     * @ORM\Column(length=255, nullable=true)
     */
    protected $externalImportIdentifier;

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
            $this->startTime = new \DateTimeImmutable();
            $this->addImportStartedEvent();
        }
    }

    /**
     * Sets an (external) identifier which allows for detecting already imported data sets.
     *
     * @param string $externalImportIdentifier
     */
    public function setExternalImportIdentifier($externalImportIdentifier)
    {
        $this->externalImportIdentifier = $externalImportIdentifier;
    }

    /**
     * @return string
     */
    public function getExternalImportIdentifier()
    {
        return $this->externalImportIdentifier;
    }

    /**
     * @param string $eventType
     * @param string $externalIdentifier
     * @param array $data
     * @param Event $parentEvent
     * @return Event
     * @throws \Neos\Neos\Exception
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
     * @return \DateTimeImmutable
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @return integer
     */
    public function getElapsedTime()
    {
        return (integer) $this->endTime->getTimestamp() - $this->startTime->getTimestamp();
    }

    /**
     * @throws Exception
     */
    public function end()
    {
        if ($this->endTime instanceof \DateTimeImmutable) {
            throw new Exception('This import has ended earlier', 1426763297);
        }
        $this->endTime = new \DateTimeImmutable();
        $this->addImportEndedEvent();
    }
}
