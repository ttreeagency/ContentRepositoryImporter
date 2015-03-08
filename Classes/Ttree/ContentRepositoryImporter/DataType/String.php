<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * String Data Type
 */
class String extends DataType {

	protected $value;

	/**
	 * @param string $value
	 */
	protected function initializeValue($value) {
		$this->value = trim($value);
		$this->value = preg_replace('/\s+/u', ' ', $value);
	}

}