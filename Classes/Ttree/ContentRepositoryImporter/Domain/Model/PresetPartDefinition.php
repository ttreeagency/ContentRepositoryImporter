<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\Exception\InvalidArgumentException;
use TYPO3\Flow\Annotations as Flow;

/**
 * Preset Part Definition
 */
class PresetPartDefinition  {

	/**
	 * @var string
	 */
	protected $currentPresetName;

	/**
	 * @var string
	 */
	protected $currentPartName;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $currentImportIdentifier;

	/**
	 * @var string
	 */
	protected $dataProviderClassName;

	/**
	 * @var array
	 */
	protected $dataProviderOptions;

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
	 * @param string $currentImportIdentifier
	 * @throws InvalidArgumentException
	 */
	public function __construct(array $setting, $currentImportIdentifier) {
		if (!isset($setting['__currentPresetName'])) {
			throw new InvalidArgumentException('Missing or invalid "__currentPresetName" in preset part settings', 1426156156);
		}
		$this->currentPresetName = trim($setting['__currentPresetName']);
		if (!isset($setting['__currentPartName'])) {
			throw new InvalidArgumentException('Missing or invalid "__currentPartName" in preset part settings', 1426156155);
		}
		$this->currentPartName = trim($setting['__currentPartName']);
		if (!isset($setting['label']) || !is_string($setting['label'])) {
			throw new InvalidArgumentException('Missing or invalid "Label" in preset part settings', 1426156157);
		}
		$this->label = (string)$setting['label'];
		if (!isset($setting['dataProviderClassName']) || !is_string($setting['dataProviderClassName'])) {
			throw new InvalidArgumentException('Missing or invalid "dataProviderClassName" in preset part settings', 1426156158);
		}
		$this->dataProviderClassName = (string)$setting['dataProviderClassName'];
		if (!isset($setting['importerClassName']) || !is_string($setting['importerClassName'])) {
			throw new InvalidArgumentException('Missing or invalid "dataProviderClassName" in preset part settings', 1426156159);
		}
		$this->importerClassName = (string)$setting['importerClassName'];
		$this->batchSize = isset($setting['batchSize']) ? (integer)$setting['batchSize'] : NULL;
		$this->offset = isset($setting['batchSize']) ? 0 : NULL;
		$this->dataProviderOptions = isset($setting['dataProviderOptions']) ? $setting['dataProviderOptions'] : [];
		$this->currentBatch = 1;
		$this->currentImportIdentifier = $currentImportIdentifier;
	}

	/**
	 * Increment the batch number
	 */
	public function nextBatch() {
		++$this->currentBatch;
		$this->offset += $this->batchSize;
	}

	/**
	 * @return string
	 */
	public function getEventType() {
		return sprintf('Preset%s:%s', ucfirst($this->currentPresetName), ucfirst($this->currentPartName));
	}

	/**
	 * @return array
	 */
	public function getCommandArguments() {
		$arguments = [
			'presetName' => $this->currentPresetName,
			'partName' => $this->currentPartName,
			'currentImportIdentifier' => $this->currentImportIdentifier,
			'dataProviderClassName' => $this->dataProviderClassName,
			'importerClassName' => $this->importerClassName,
			'currentBatch' => $this->currentBatch
		];
		if ($this->batchSize) {
			$arguments['batchSize'] = (integer)$this->batchSize;
		}
		if ($this->offset) {
			$arguments['offset'] = (integer)$this->offset;
		}
		return $arguments;
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
	public function getCurrentImportIdentifier() {
		return $this->currentImportIdentifier;
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