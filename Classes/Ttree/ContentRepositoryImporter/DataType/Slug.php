<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use Cocur\Slugify\Slugify;
use TYPO3\Flow\Annotations as Flow;

/**
 * String Data Type
 */
class Slug extends DataType {

	protected $value;

	/**
	 * @param string $value
	 */
	protected function initializeValue($value) {
		$value = new String($value);
		$slugify = new Slugify();
		$this->value = $slugify->slugify($value->getValue());
	}

}