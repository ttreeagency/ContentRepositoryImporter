<?php
namespace Ttree\ContentRepositoryImporter\Importer;

use Ttree\ContentRepositoryImporter\DataProvider\DataProviderInterface;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeTemplate;

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
