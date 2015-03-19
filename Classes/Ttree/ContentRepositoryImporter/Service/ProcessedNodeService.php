<?php
namespace Ttree\ContentRepositoryImporter\Service;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\Domain\Model\RecordMapping;
use Ttree\ContentRepositoryImporter\Domain\Repository\RecordMappingRepository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;

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
	 * @Flow\Inject
	 * @var RecordMappingRepository
	 */
	protected $recordMappingRepository;

	/**
	 * @param string $importerClassName
	 * @param string $externalIdentifier
	 */
	public function set($importerClassName, $externalIdentifier) {
		$entryIdentifier = $this->getEntryIdentifier($importerClassName, $externalIdentifier);
		$this->cache->set($entryIdentifier, TRUE);
	}

	/**
	 * @param string $importerClassName
	 * @param string $externalIdentifier
	 * @return RecordMapping
	 */
	public function get($importerClassName, $externalIdentifier) {
		$recordMapping = NULL;
		$entryIdentifier = $this->getEntryIdentifier($importerClassName, $externalIdentifier);
		if ($this->cache->has($entryIdentifier)) {
			$recordMapping = $this->recordMappingRepository->findOneByImporterClassNameAndExternalIdentifier($importerClassName, $externalIdentifier);
		}

		return $recordMapping;
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