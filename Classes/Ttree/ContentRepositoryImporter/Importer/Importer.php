<?php
namespace Ttree\ContentRepositoryImporter\Importer;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

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
abstract class Importer implements ImporterInterface {

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
	 * @param array $options
	 * @param string $currentImportIdentifier
	 */
	public function __construct(array $options, $currentImportIdentifier) {
		$this->options = $options;
		$this->currentImportIdentifier = $currentImportIdentifier;
	}

	/**
	 * Resume the current Import
	 *
	 * This is required because we use sub request in CLI controller
	 */
	public function initializeObject() {
		$this->importService->resume($this->currentImportIdentifier);
	}

	/**
	 * @return DataProviderInterface
	 */
	public function getDataProvider() {
		return $this->dataProvider;
	}

	/**
	 * @return ImportService
	 */
	public function getImportService() {
		return $this->importService;
	}

	/**
	 * @param Event $event
	 */
	public function setCurrentEvent(Event $event) {
		$this->currentEvent = $event;
	}

	/**
	 * Initialize import context
	 * @param DataProviderInterface $dataProvider
	 * @throws Exception
	 */
	public function initialize(DataProviderInterface $dataProvider) {
		$this->dataProvider = $dataProvider;
		$contextConfiguration = ['workspaceName' => 'live', 'invisibleContentShown' => TRUE];
		$context = $this->contextFactory->create($contextConfiguration);
		$this->rootNode = $context->getRootNode();

		$siteNodePath = $this->options['siteNodePath'];
		$this->siteNode = $this->rootNode->getNode($siteNodePath);
		if ($this->siteNode === NULL) {
			throw new Exception(sprintf('Site node not found (%s)', $siteNodePath), 1425077201);
		}
	}

	/**
	 * Import data from the given data provider
	 *
	 * @param NodeTemplate $nodeTemplate
	 */
	protected function processBatch(NodeTemplate $nodeTemplate = NULL) {
		$records = $this->dataProvider->fetch();
		array_walk($records, function ($data) use ($nodeTemplate) {
			$this->processRecord($nodeTemplate, $data);
		});
	}

	/**
	 * @param NodeTemplate $nodeTemplate
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 */
	protected function unsetAllNodeTemplateProperties(NodeTemplate $nodeTemplate) {
		foreach ($nodeTemplate->getPropertyNames() as $propertyName) {
			if (!$nodeTemplate->hasProperty($propertyName)) {
				continue;
			}
			$nodeTemplate->removeProperty($propertyName);
		}
	}

	/**
	 * @param string $externalIdentifier
	 * @param string $nodeName
	 * @param NodeInterface $storageNode
	 * @param boolean $skipExistingNode
	 * @param bool $skipAlreadyProcessed
	 * @return bool
	 * @throws Exception
	 */
	protected function skipNodeProcessing($externalIdentifier, $nodeName, NodeInterface $storageNode, $skipExistingNode = TRUE, $skipAlreadyProcessed = TRUE) {
		if ($skipAlreadyProcessed === TRUE && $this->getNodeProcessing($externalIdentifier)) {
			$this->importService->addEventMessage('Node:Processed:Skipped', 'Skip already processed', LOG_NOTICE, $this->currentEvent);
			return TRUE;
		}
		$node = $storageNode->getNode($nodeName);
		if ($skipExistingNode === TRUE && $node instanceof NodeInterface) {
			$this->importService->addEventMessage('Node:Existing:Skipped', 'Skip existing node', LOG_WARNING, $this->currentEvent);
			$this->registerNodeProcessing($node, $externalIdentifier);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param NodeInterface $node
	 * @param string $externalIdentifier
	 * @param string $externalRelativeUri
	 */
	protected function registerNodeProcessing(NodeInterface $node, $externalIdentifier, $externalRelativeUri = NULL) {
		$this->importService->addOrUpdateRecordMapping(get_called_class(), $externalIdentifier, $externalRelativeUri, $node->getIdentifier(), $node->getPath());
		$this->processedNodeService->set(get_called_class(), $externalIdentifier);
	}

	/**
	 * @param string $externalIdentifier
	 * @return RecordMapping
	 */
	protected function getNodeProcessing($externalIdentifier) {
		return $this->processedNodeService->get(get_called_class(), $externalIdentifier);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function log($message, $severity = LOG_INFO) {
		$this->importService->addEventMessage('Record:Import:Action:Reported', $message, $severity, $this->currentEvent);
	}

}