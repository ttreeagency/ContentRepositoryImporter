<?php

namespace Ttree\ContentRepositoryImporter\Importer;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Property\PropertyMapper;
use Ttree\ContentRepositoryImporter\DataProvider\DataProviderInterface;
use Ttree\ContentRepositoryImporter\DataType\Slug;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Model\RecordMapping;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Ttree\ContentRepositoryImporter\Exception\SiteNodeEmptyException;
use Ttree\ContentRepositoryImporter\Service\DimensionsImporter;
use Ttree\ContentRepositoryImporter\Service\NodePropertyMapper;
use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Utility\Arrays;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Ttree\ContentRepositoryImporter\Service\Vault;

/**
 * Abstract Importer
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * The unique identifier of the storage node
     * @var string
     */
    protected $storageNodeIdentifier;

    /**
     * Node path (can be absolute or relative to the current site node) where the "storage node" (ie. the parent
     * document node for nodes imported by the concrete importer) will be located. You can also use the identifier of the node.
     *
     * @var string
     */
    protected $storageNodeNodePath = 'exampleStorage';

    /**
     * A label / title for the storage document node
     *
     * @var string
     */
    protected $storageNodeTitle = 'Storage';

    /**
     * Name of the node type to use for creating a new storage node, if needed
     *
     * @var string
     */
    protected $storageNodeTypeName = 'Neos.NodeTypes:Page';

    /**
     * Key name for getExternalIdentifierFromRecordData() to determine the external identifier of a record
     * from $commandData. Set this to the actual key (or nested key path, like 'foo.bar.baz') in the concrete
     * import scenario or override getExternalIdentifierFromRecordData().
     *
     * @var string
     * @api
     */
    protected $externalIdentifierDataKey = '__externalIdentifier';

    /**
     * Key name for getLabelFromCommandData() to determine a meaningful label for a record from $commandData.
     * Set this to the actual key (or nested key path) in the concrete import scenario or override getLabelFromCommandData()
     *
     * @var string
     * @api
     */
    protected $labelDataKey = '__label';

    /**
     * If set, names of new nodes created by this importer will be prefixed with the given string
     *
     * @var string
     * @api
     */
    protected $nodeNamePrefix;

    /**
     * If your concrete importer processes commands only for one specific node type (for example, a Product node type),
     * you can specify that node type name here (for example "Acme.Demo:Product").
     *
     * If your importer needs to work with multiple node types, you may need to implement your own processRecord() method.
     *
     * @var string
     * @api
     */
    protected $nodeTypeName;

    /**
     * "Main" node type. Will be set by initializeNodeTemplates()
     *
     * @var NodeType
     */
    protected $nodeType;

    /**
     * "Main" node template. Will be set by initializeNodeTemplates()
     *
     * @var NodeTemplate
     */
    protected $nodeTemplate;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

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
     * @var DimensionsImporter
     * @Flow\Inject
     */
    protected $dimensionsImporter;

    /**
     * @var NodePropertyMapper
     * @Flow\Inject
     */
    protected $nodePropertyMapper;

    /**
     * @var Vault
     */
    protected $vault;

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
     * @var string
     */
    protected $presetName;

    /**
     * @var string
     */
    protected $partName;

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
     * @param Vault|null $vault
     */
    public function __construct(array $options, $currentImportIdentifier, Vault $vault)
    {
        $this->options = $options;
        $this->presetName = $options['__presetName'];
        $this->partName = $options['__partName'];
        $this->vault = $vault;
        unset($this->options['__presetName'], $this->options['__partName']);
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

        $this->applyOption($this->nodeTypeName, 'nodeTypeName');
        $this->applyOption($this->storageNodeIdentifier, 'storageNodeIdentifier');
        $this->applyOption($this->storageNodeNodePath, 'storageNodeNodePath');

        $siteNodePath = isset($this->options['siteNodePath']) ? trim($this->options['siteNodePath']) : null;
        $siteNodeIdentifier = isset($this->options['siteNodeIdentifier']) ? trim($this->options['siteNodeIdentifier']) : null;

        if (isset($this->storageNodeIdentifier)) {
            $this->storageNode = $context->getNodeByIdentifier($this->storageNodeIdentifier);
            if (!($this->storageNode instanceof NodeInterface)) {
                throw new Exception(sprintf('The storage node with identifier "%s" was not found', $this->storageNodeIdentifier));
            }

            $pathParts = explode('/', ltrim($this->storageNode->getPath(), '/'));
            $siteNodePath = '/sites/' . $pathParts[1];

            $this->storageNodeNodePath = $this->storageNode->getPath();
        }

        if (isset($siteNodePath) || isset($siteNodeIdentifier)) {
            $this->siteNode = $this->rootNode->getNode($siteNodePath) ?: $context->getNodeByIdentifier($siteNodeIdentifier);
            if ($this->siteNode === null) {
                throw new Exception(sprintf('Site node not found (%s)', $siteNodePath ?: $siteNodeIdentifier), 1425077201);
            }
        } else {
            $this->log(get_class($this) . ': siteNodePath is not defined. Please make sure to set the target siteNodePath in your importer options.', LOG_WARNING);
        }
    }

    protected function applyOption(&$option, $optionName)
    {
        $option = isset($this->options[$optionName]) ? $this->options[$optionName] : $option;
    }

    /**
     * Starts batch processing all commands
     *
     * Override this method if you would like some other way of initialization.
     *
     * @return void
     * @api
     */
    public function process()
    {
        $this->initializeStorageNode($this->storageNodeNodePath, $this->storageNodeTitle);
        $this->initializeNodeTemplates();

        $nodeTemplate = new NodeTemplate();
        $this->processBatch($nodeTemplate);
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
            if (!\is_array($data)) {
                $data = $this->propertyMapper->convert($data, 'array');
            }
            $this->processRecord($nodeTemplate, $data);
            ++$this->processedRecords;
        });
        $this->postProcessing($records);
    }


    public function withStorageNode(NodeInterface $storageNode, \Closure $closure)
    {
        $previousStorageNode = $this->storageNode;
        try {
            $this->storageNode = $storageNode;
            $closure();
            $this->storageNode = $previousStorageNode;
        } catch (\Exception $exception) {
            $this->storageNode = $previousStorageNode;
            throw $exception;
        }
    }

    /**
     * Processes a single record
     *
     * Override this method if you need a different approach.
     *
     * @param NodeTemplate $nodeTemplate
     * @param array $data
     * @return NodeInterface
     * @throws \Exception
     * @api
     */
    public function processRecord(NodeTemplate $nodeTemplate, array $data)
    {
        $this->unsetAllNodeTemplateProperties($nodeTemplate);

        $externalIdentifier = $this->getExternalIdentifierFromRecordData($data);
        $nodeName = $this->renderNodeName($externalIdentifier);
        if (!isset($data['uriPathSegment'])) {
            $data['uriPathSegment'] = Slug::create($this->getLabelFromRecordData($data))->getValue();
        }

        $recordMapping = $this->getNodeProcessing($externalIdentifier);
        if ($recordMapping !== null) {
            $node = $this->storageNode->getContext()->getNodeByIdentifier($recordMapping->getNodeIdentifier());
            if ($node === null) {
                throw new \Exception(sprintf('Failed retrieving existing node for update. External identifier: %s Node identifier: %s. Maybe the record mapping in the database does not match the existing (imported) nodes anymore.', $externalIdentifier, $recordMapping->getNodeIdentifier()), 1462971366085);
            }
            $this->applyProperties($this->getPropertiesFromDataProviderPayload($data), $node);

        } else {
            $nodeTemplate->setNodeType($this->nodeType);
            $nodeTemplate->setName($nodeName);
            $this->applyProperties($this->getPropertiesFromDataProviderPayload($data), $nodeTemplate);

            $node = $this->createNodeFromTemplate($nodeTemplate, $data);
            $this->registerNodeProcessing($node, $externalIdentifier);
        }

        $this->dimensionsImporter->process($node, $this->getPropertiesFromDataProviderPayload($data), $this->currentEvent);

        return $node;
    }

    /**
     * @param NodeTemplate $templace
     * @param array $data
     * @return NodeInterface
     */
    protected function createNodeFromTemplate(NodeTemplate $templace, array $data)
    {
        return $this->storageNode->createNodeFromTemplate($templace);
    }

    /**
     * @param NodeTemplate $nodeTemplate
     * @throws \Neos\ContentRepository\Exception\NodeException
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
     * @param array $data
     * @return array
     */
    protected function getPropertiesFromDataProviderPayload(array $data)
    {
        return $data;
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
        return $this->nodePropertyMapper->map($data, $nodeOrTemplate, $this->currentEvent);
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
        $this->processedNodeService->set(get_called_class(), $externalIdentifier, $externalRelativeUri, $node->getIdentifier(), $node->getPath(), $this->presetPath());
    }

    /**
     * @param string $externalIdentifier
     * @return RecordMapping
     */
    protected function getNodeProcessing($externalIdentifier)
    {
        return $this->processedNodeService->get(get_called_class(), $externalIdentifier, $this->presetPath());
    }

    /**
     * @return string
     */
    protected function presetPath()
    {
        return $this->presetName . '/' . $this->partName;
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

    /**
     * Returns the external identifier of a record by looking it up in $data
     *
     * Either override this method for your own purpose or simply set $this->externalIdentifierDataKey
     *
     * @param array $data
     * @return string
     * @throws \Exception
     * @api
     */
    protected function getExternalIdentifierFromRecordData(array $data)
    {
        $externalIdentifier = Arrays::getValueByPath($data, $this->externalIdentifierDataKey);
        if ($externalIdentifier === null) {
            throw new \Exception('Could not determine external identifier from record data. See ' . self::class . ' for more information.', 1462968317292);
        }
        return (string) $externalIdentifier;
    }

    /**
     * Render a valid node name for a new Node based on the current record
     *
     * @param string $externalIdentifier External identifier of the current record
     * @return string
     */
    protected function renderNodeName($externalIdentifier)
    {
        return Slug::create(($this->nodeNamePrefix !== null ? $this->nodeNamePrefix : uniqid()) . $externalIdentifier)->getValue();
    }

    /**
     * Returns a label for a record by looking it up in $data
     *
     * Either override this method for your own purpose or simply set $this->labelDataKey
     *
     * @param array $data
     * @return string
     * @throws \Exception
     * @api
     */
    protected function getLabelFromRecordData(array $data)
    {
        $label = Arrays::getValueByPath($data, $this->labelDataKey);
        if ($label === null) {
            throw new \Exception('Could not determine label from record data (key: ' . $this->labelDataKey . '). See ' . self::class . ' for more information.', 1462968958372);
        }
        return (string) $label;
    }


    /**
     * Make sure that a (document) node exists which acts as a parent for nodes imported by this importer.
     *
     * The storage node is either created or just retrieved and finally stored in $this->storageNode.
     *
     * @param string $nodePathOrIdentifier A nodeIdentifier (prefixed with #) or an absolute or relative (to the site node) node path of the storage node
     * @param string $title Title for the storage node document
     * @return void
     * @throws Exception
     */
    protected function initializeStorageNode($nodePathOrIdentifier, $title)
    {
        if (is_string($nodePathOrIdentifier) && $nodePathOrIdentifier[0] === '#') {
            $this->storageNode = $this->rootNode->getContext()->getNodeByIdentifier(\substr($nodePathOrIdentifier, 1));
        } else {
            $this->storageNode = $this->getSiteNode()->getNode($nodePathOrIdentifier);

            preg_match('|([a-z0-9\-]+/)*([a-z0-9\-]+)$|', $nodePathOrIdentifier, $matches);
            $nodeName = $matches[2];
            $uriPathSegment = Slug::create($title)->getValue();

            $storageNodeTemplate = new NodeTemplate();
            $storageNodeTemplate->setNodeType($this->nodeTypeManager->getNodeType($this->storageNodeTypeName));

            if ($this->storageNode === null) {
                $storageNodeTemplate->setProperty('title', $title);
                $storageNodeTemplate->setProperty('uriPathSegment', $uriPathSegment);
                $storageNodeTemplate->setName($nodeName);
                $this->storageNode = $this->getSiteNode()->createNodeFromTemplate($storageNodeTemplate);
            }
        }

        if (!$this->storageNode instanceof NodeInterface) {
            throw new Exception('Storage node can not be empty', 1500558744);
        }
    }

    /**
     * @return NodeInterface
     * @throws SiteNodeEmptyException
     */
    protected function getSiteNode()
    {
        if (!$this->siteNode instanceof NodeInterface) {
            throw new SiteNodeEmptyException(get_class($this) . ': siteNodePath is not defined. Please make sure to set the target siteNodePath in your importer options.');
        }
        return $this->siteNode;
    }


    /**
     * Initializes the node template(s) used by this importer.
     *
     * Override this method if you need to work with other / multiple node types.
     *
     * @return void
     * @api
     */
    protected function initializeNodeTemplates()
    {
        $this->nodeType = $this->nodeTypeManager->getNodeType($this->nodeTypeName);
        $this->nodeTemplate = new NodeTemplate();
        $this->nodeTemplate->setNodeType($this->nodeType);
    }
}
