<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use TYPO3\Flow\Annotations as Flow;

/**
 * String Data Type
 */
class Integer extends String {

	/**
	 * @return integer
	 */
	public function getValue() {
		$this->initializeValue($this->rawValue);
		return (integer)$this->value;
	}

}