<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms;

/**
 * Preset Part Definition
 */
class PresetPartDefinition  {

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $logPrefix;

	/**
	 * @var string
	 */
	protected $dataProviderClassName;

	/**
	 * @var string
	 */
	protected $importerClassName;

	/**
	 * @var integer
	 */
	protected $currentBatch;

	/**
	 * @var integer
	 */
	protected $batchSize;

	/**
	 * @var integer
	 */
	protected $offset;

	/**
	 * @param array $setting
	 * @param string $logPrefix
	 */
	public function __construct(array $setting, $logPrefix = NULL) {
		$this->label = $setting['label'];
		$this->dataProviderClassName = $setting['dataProviderClassName'];
		$this->importerClassName = $setting['importerClassName'];
		$this->batchSize = isset($setting['batchSize']) ? (integer)$setting['batchSize'] : NULL;
		$this->offset = isset($setting['batchSize']) ? 0 : NULL;
		$this->currentBatch = 1;
		$this->logPrefix = $logPrefix ?: Algorithms::generateRandomString(12);
	}

	/**
	 * Increment the batch number
	 */
	public function nextBatch() {
		++$this->currentBatch;
		$this->offset += $this->batchSize;
	}

	/**
	 * @return array
	 */
	public function getCommandArguments() {
		return [
			'logPrefix' => $this->logPrefix,
			'dataProviderClassName' => $this->dataProviderClassName,
			'importerClassName' => $this->importerClassName,
			'currentBatch' => $this->currentBatch,
			'batchSize' => $this->batchSize,
			'offset' => $this->offset
		];
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getLogPrefix() {
		return $this->logPrefix;
	}

	/**
	 * @return string
	 */
	public function getDataProviderClassName() {
		return $this->dataProviderClassName;
	}

	/**
	 * @return string
	 */
	public function getImporterClassName() {
		return $this->importerClassName;
	}

	/**
	 * @return int
	 */
	public function getCurrentBatch() {
		return $this->currentBatch;
	}

	/**
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

}