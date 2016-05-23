<?php
namespace Ttree\ContentRepositoryImporter\Importer;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Ttree\ContentRepositoryImporter\DataProvider\DataProviderInterface;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Model\RecordMapping;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Abstract Importer
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ImportService
     */
    protected $importService;

    /**
     * @var string
     */
    protected $currentImportIdentifier;

    /**
     * @Flow\Inject
     * @var ProcessedNodeService
     */
    protected $processedNodeService;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeInterface
     */
    protected $rootNode;

    /**
     * @var NodeInterface
     */
    protected $siteNode;

    /**
     * @var NodeInterface
     */
    protected $storageNode;

    /**
     * @var DataProviderInterface
     */
    protected $dataProvider;

    /**
     * @Flow\InjectConfiguration(package="Ttree.ContentRepositoryImporter")
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var Event
     */
    protected $currentEvent;

    /**
     * @var integer
     */
    protected $processedRecords = 0;

    /**
     * Mapping between severity constants and string
     *
     * @var array
     */
    protected $severityLabels = [
        LOG_EMERG => 'Emergency',
        LOG_ALERT => 'Alert',
        LOG_CRIT => 'Critcyl',
        LOG_ERR => 'Error',
        LOG_WARNING => 'Warning',
        LOG_NOTICE => 'Notice',
        LOG_INFO => 'Info',
        LOG_DEBUG => 'Debug',
    ];

    /**
     * @param array $options
     * @param string $currentImportIdentifier
     */
    public function __construct(array $options, $currentImportIdentifier)
    {
        $this->options = $options;
        $this->currentImportIdentifier = $currentImportIdentifier;
    }

    /**
     * Resume the current Import
     *
     * This is required because we use sub request in CLI controller
     */
    public function initializeObject()
    {
        $this->importService->resume($this->currentImportIdentifier);
    }

    /**
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * @return ImportService
     */
    public function getImportService()
    {
        return $this->importService;
    }

    /**
     * @param Event $event
     */
    public function setCurrentEvent(Event $event)
    {
        $this->currentEvent = $event;
    }

    /**
     * @return Event
     */
    public function getCurrentEvent()
    {
        return $this->currentEvent;
    }

    /**
     * @return integer
     */
    public function getProcessedRecords()
    {
        return $this->processedRecords;
    }

    /**
     * Initialize import context
     *
     * @param DataProviderInterface $dataProvider
     * @throws Exception
     */
    public function initialize(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
        $contextConfiguration = ['workspaceName' => 'live', 'invisibleContentShown' => true];
        $context = $this->contextFactory->create($contextConfiguration);
        $this->rootNode = $context->getRootNode();

        if (isset($this->options['siteNodePath'])) {
            $siteNodePath = $this->options['siteNodePath'];
            $this->siteNode = $this->rootNode->getNode($siteNodePath);
            if ($this->siteNode === null) {
                throw new Exception(sprintf('Site node not found (%s)', $siteNodePath), 1425077201);
            }
        } else {
            $this->log(get_class($this) . ': siteNodePath is not defined. Please make sure to set the target siteNodePath in your importer options.', LOG_WARNING);
        }
    }

    /**
     * Import data from the given data provider
     *
     * @param NodeTemplate $nodeTemplate
     * @throws Exception
     */
    protected function processBatch(NodeTemplate $nodeTemplate = null)
    {
        $records = $this->dataProvider->fetch();
        if (!is_array($records)) {
            throw new Exception(sprintf('Expected records as an array while calling %s->fetch(), but returned %s instead.', get_class($this->dataProvider), gettype($records)), 1462960769826);
        }
        $records = $this->preProcessing($records);
        array_walk($records, function ($data) use ($nodeTemplate) {
            $this->processRecord($nodeTemplate, $data);
            ++$this->processedRecords;
        });
        $this->postProcessing($records);
    }

    /**
     * @param NodeTemplate $nodeTemplate
     * @throws \TYPO3\TYPO3CR\Exception\NodeException
     */
    protected function unsetAllNodeTemplateProperties(NodeTemplate $nodeTemplate)
    {
        foreach ($nodeTemplate->getPropertyNames() as $propertyName) {
            if (!$nodeTemplate->hasProperty($propertyName)) {
                continue;
            }
            $nodeTemplate->removeProperty($propertyName);
        }
    }

    /**
     * Applies the given properties ($data) to the given Node or NodeTemplate
     *
     * @param array $data Property names and property values
     * @param NodeInterface|NodeTemplate $nodeOrTemplate The Node or Node Template
     * @return boolean True if an existing node has been modified, false if the new properties are the same like the old ones
     */
    protected function applyProperties(array $data, $nodeOrTemplate)
    {
        if (!$nodeOrTemplate instanceof NodeInterface && !$nodeOrTemplate instanceof NodeTemplate) {
            throw new \InvalidArgumentException(sprintf('$nodeOrTemplate must be either an object implementing NodeInterface or a NodeTemplate, %s given.', (is_object($nodeOrTemplate) ? get_class($nodeOrTemplate) : gettype($nodeOrTemplate))), 1462958554616);
        }
        $nodeChanged = false;
        foreach ($data as $propertyName => $propertyValue) {
            if (substr($propertyName, 0, 1) === '_') {
                continue;
            }
            if ($nodeOrTemplate->getProperty($propertyName) != $propertyValue) {
                $nodeOrTemplate->setProperty($propertyName, $propertyValue);
                $nodeChanged = true;
            }
        }
        return $nodeChanged;
    }

    /**
     * Preprocess RAW data
     *
     * @param array $records
     * @return array
     */
    protected function preProcessing(array $records)
    {
        return $records;
    }

    /**
     * Postprocessing
     *
     * @param array $records
     */
    protected function postProcessing(array $records)
    {
    }

    /**
     * Checks if processing / import of the record specified by $externalIdentifier should be skipped.
     *
     * The following criteria for skipping the processing exist:
     *
     * 1) a record with the given external identifier already has been processed in the past
     * 2) a node with a node name equal to what a new node would have already exists
     *
     * These criteria can be enabled or disabled through $skipExistingNode and $skipAlreadyProcessed.
     *
     * @param string $externalIdentifier
     * @param string $nodeName
     * @param NodeInterface $storageNode
     * @param boolean $skipExistingNode
     * @param bool $skipAlreadyProcessed
     * @return bool
     * @throws Exception
     */
    protected function skipNodeProcessing($externalIdentifier, $nodeName, NodeInterface $storageNode, $skipExistingNode = true, $skipAlreadyProcessed = true)
    {
        if ($skipAlreadyProcessed === true && $this->getNodeProcessing($externalIdentifier)) {
            $this->importService->addEventMessage('Node:Processed:Skipped', 'Skip already processed', LOG_NOTICE, $this->currentEvent);
            return true;
        }
        $node = $storageNode->getNode($nodeName);
        if ($skipExistingNode === true && $node instanceof NodeInterface) {
            $this->importService->addEventMessage('Node:Existing:Skipped', 'Skip existing node', LOG_WARNING, $this->currentEvent);
            $this->registerNodeProcessing($node, $externalIdentifier);
            return true;
        }

        return false;
    }

    /**
     * @param NodeInterface $node
     * @param string $externalIdentifier
     * @param string $externalRelativeUri
     */
    protected function registerNodeProcessing(NodeInterface $node, $externalIdentifier, $externalRelativeUri = null)
    {
        if (defined('static::IMPORTER_CLASSNAME') === false) {
            $importerClassName = get_called_class();
        } else {
            $importerClassName = static::IMPORTER_CLASSNAME;
        }
        $this->processedNodeService->set($importerClassName, $externalIdentifier, $externalRelativeUri, $node->getIdentifier(), $node->getPath());
    }

    /**
     * @param string $externalIdentifier
     * @return RecordMapping
     */
    protected function getNodeProcessing($externalIdentifier)
    {
        return $this->processedNodeService->get(get_called_class(), $externalIdentifier);
    }

    /**
     * Create an entry in the event log
     */
    protected function log($message, $severity = LOG_INFO)
    {
        if (!isset($this->severityLabels[$severity])) {
            throw new Exception('Invalid severity value', 1426868867);
        }
        $this->importService->addEventMessage(sprintf('Record:Import:Log:%s', $this->severityLabels[$severity]), $message, $severity, $this->currentEvent);
    }
}
