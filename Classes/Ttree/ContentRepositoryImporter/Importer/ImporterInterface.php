<?php
namespace Ttree\ContentRepositoryImporter\Importer;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\DataProvider\DataProviderInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * Abstract Importer
 */
interface ImporterInterface {

	/**
	 * @param DataProviderInterface $dataProvider
	 */
	public function import(DataProviderInterface $dataProvider);

	/**
	 * @param string $logPrefix
	 */
	public function setLogPrefix($logPrefix);

}