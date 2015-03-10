<?php
namespace Ttree\ContentRepositoryImporter\Service;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\Domain\Model\ProcessedNodeDefinition;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Processed Node Service
 *
 * @Flow\Scope("singleton")
 */
class ProcessedNodeService  {

	/**
	 * @var VariableFrontend
	 */
	protected $cache;

	/**
	 * @param string $importerClassName
	 * @param string $externalIdentifier
	 * @param NodeInterface $node
	 */
	public function set($importerClassName, $externalIdentifier, NodeInterface $node) {
		$processedNode = ProcessedNodeDefinition::createFromNode($externalIdentifier, $node);
		$entryIdentifier = $this->getEntryIdentifier($importerClassName, $externalIdentifier);
		$this->cache->set($entryIdentifier, $processedNode);
	}

	/**
	 * @param string $importerClassName
	 * @param string $externalIdentifier
	 * @return ProcessedNodeDefinition
	 */
	public function get($importerClassName, $externalIdentifier) {
		$entryIdentifier = $this->getEntryIdentifier($importerClassName, $externalIdentifier);
		return $this->cache->get($entryIdentifier);
	}

	/**
	 * @param string $importerClassName
	 * @param string $externalIdentifier
	 * @return string
	 */
	protected function getEntryIdentifier($importerClassName, $externalIdentifier) {
		return md5($importerClassName . $externalIdentifier);
	}

}