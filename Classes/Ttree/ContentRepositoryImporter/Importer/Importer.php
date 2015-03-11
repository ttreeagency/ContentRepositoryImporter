<?php
namespace Ttree\ContentRepositoryImporter\Importer;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\Domain\Model\ProcessedNodeDefinition;
use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
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
	 * @var string
	 */
	protected $logPrefix;

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
	 * @Flow\Inject(setting="import")
	 * @var array
	 */
	protected $configuration = [];

	/**
	 * Initialize
	 */
	protected function initialize() {
		$this->logPrefix = $this->logPrefix ?: Algorithms::generateRandomString(12);

		$contextConfiguration = ['workspaceName' => 'live', 'invisibleContentShown' => TRUE];
		$context = $this->contextFactory->create($contextConfiguration);
		$this->rootNode = $context->getRootNode();

		# Todo add configuration to set site node
		$siteNodePath = 'sites/architectesch';
		$this->siteNode = $this->rootNode->getNode($siteNodePath);
		if ($this->siteNode === NULL) {
			throw new Exception(sprintf('Site node not found (%s)', $siteNodePath), 1425077201);
		}
	}

	/**
	 * @param string $logPrefix
	 */
	public function setLogPrefix($logPrefix) {
		$this->logPrefix = $logPrefix;
	}

	/**
	 * @param string $name
	 * @param string $externalIdentifier
	 * @param string $nodeName
	 * @param NodeInterface $storageNode
	 * @param boolean $skipExistingNode
	 * @return boolean
	 */
	protected function skipNodeProcessing($name, $externalIdentifier, $nodeName, NodeInterface $storageNode, $skipExistingNode = TRUE) {
		if ($this->getNodeProcessing($externalIdentifier)) {
			$this->log(sprintf('- Skip already processed node "%s" ...', $name), LOG_NOTICE);
			return TRUE;
		}
		$node = $storageNode->getNode($nodeName);
		if ($skipExistingNode === TRUE && $node instanceof NodeInterface) {
			$this->log(sprintf('- Skip existing node "%s" ...', $name), LOG_WARNING);
			$this->registerNodeProcessing($node, $externalIdentifier);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param NodeInterface $node
	 * @param string $externalIdentifier
	 */
	protected function registerNodeProcessing(NodeInterface $node, $externalIdentifier) {
		$this->processedNodeService->set(get_called_class(), $externalIdentifier, $node);
	}

	/**
	 * @param string $externalIdentifier
	 * @return ProcessedNodeDefinition
	 */
	protected function getNodeProcessing($externalIdentifier) {
		return $this->processedNodeService->get(get_called_class(), $externalIdentifier);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		$message = sprintf('[%s] %s', $this->logPrefix, $message);
		$this->logger->log($message, $severity, $additionalData, $packageKey, $className, $methodName);
	}

}