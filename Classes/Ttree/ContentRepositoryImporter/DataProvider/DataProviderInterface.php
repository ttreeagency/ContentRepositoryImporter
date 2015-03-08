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
	 * @param integer $offset
	 * @param integer $limit
	 * @return DataProviderInterface
	 */
	public static function create($offset = NULL, $limit = NULL);

	/**
	 * @return array
	 */
	public function fetch();

	/**
	 * @param integer $limit
	 * @return void
	 */
	public function setLimit($limit);

	/**
	 * @param integer $offset
	 * @return void
	 */
	public function setOffset($offset);

	/**
	 * @return boolean
	 */
	public function hasLimit();

}