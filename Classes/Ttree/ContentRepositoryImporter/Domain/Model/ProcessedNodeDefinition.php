<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Processed Node Definition
 */
class ProcessedNodeDefinition  {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $externalIdentifier;

	/**
	 * @param string $identifier
	 * @param string $path
	 * @param string $externalIdentifier
	 */
	public function __construct($identifier, $path, $externalIdentifier) {
		$this->identifier = (string)$identifier;
		$this->path = (string)$path;
		$this->externalIdentifier = (string)$externalIdentifier;
	}

	/**
	 * @param string $externalIdentifier
	 * @param NodeInterface $node
	 * @return ProcessedNodeDefinition
	 */
	public static function createFromNode($externalIdentifier, NodeInterface $node) {
		return new ProcessedNodeDefinition($node->getIdentifier(), $node->getPath(), $externalIdentifier);
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getExternalIdentifier() {
		return $this->externalIdentifier;
	}

}