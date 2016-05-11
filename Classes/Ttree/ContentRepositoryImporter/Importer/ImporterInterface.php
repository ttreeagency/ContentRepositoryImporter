<?php
namespace Ttree\ContentRepositoryImporter\Importer;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Ttree\ContentRepositoryImporter\DataProvider\DataProviderInterface;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;

/**
 * Importer Interface
 */
interface ImporterInterface
{
    /**
     * @param DataProviderInterface $dataProvider
     */
    public function initialize(DataProviderInterface $dataProvider);

    /**
     * Import from the current DataProvider
     */
    public function process();

    /**
     * @param NodeTemplate $nodeTemplate
     * @param array $data
     */
    public function processRecord(NodeTemplate $nodeTemplate, array $data);

    /**
     * @return DataProviderInterface
     */
    public function getDataProvider();

    /**
     * @return ImportService
     */
    public function getImportService();

    /**
     * @param Event $event
     */
    public function setCurrentEvent(Event $event);

    /**
     * @return Event
     */
    public function getCurrentEvent();

    /**
     * @return integer
     */
    public function getProcessedRecords();
}
