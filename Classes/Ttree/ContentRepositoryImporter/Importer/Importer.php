<?php
namespace Ttree\ContentRepositoryImporter\Importer;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

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
	 * Used as a prototype to avoid creating the object again and again.
	 *
	 * @var \DateTime
	 */
	protected $publishedDateTime;

	/**
	 * Used as a prototype to avoid creating the object again and again.
	 *
	 * @var \DateTime
	 */
	protected $hiddenBeforeDateTime;

	/**
	 * Used as a prototype to avoid creating the object again and again.
	 *
	 * @var \DateTime
	 */
	protected $hiddenAfterDateTime;

	/**
	 * @Flow\Inject(setting="import")
	 * @var array
	 */
	protected $configuration = [];

	/**
	 * Initialize
	 */
	protected function initialize() {
		$this->publishedDateTime = new \DateTime();
		$this->hiddenBeforeDateTime = new \DateTime();
		$this->hiddenAfterDateTime = new \DateTime();

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
	 * {@inheritdoc}
	 */
	protected function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		$message = sprintf('[%s] %s', $this->logPrefix, $message);
		$this->logger->log($message, $severity, $additionalData, $packageKey, $className, $methodName);
	}

}