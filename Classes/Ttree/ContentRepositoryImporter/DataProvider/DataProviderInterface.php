<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Data Provider Interface
 */
interface DataProviderInterface {

	/**
	 * @return array
	 */
	public function fetch();

	/**
	 * @return boolean
	 */
	public function hasLimit();

}