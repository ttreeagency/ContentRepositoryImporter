<?php
namespace Ttree\ContentRepositoryImporter\Service;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Ttree\ContentRepositoryImporter\Domain\Model\RecordMapping;
use Ttree\ContentRepositoryImporter\Domain\Repository\RecordMappingRepository;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Neos\Flow\Annotations as Flow;

/**
 * Processed Node Service
 *
 * @Flow\Scope("singleton")
 */
class ProcessedNodeService
{
    /**
     * @Flow\Inject
     * @var ImportService
     */
    protected $importService;

    /**
     * @Flow\Inject
     * @var RecordMappingRepository
     */
    protected $recordMappingRepository;

    /**
     * @param string $importerClassName
     * @param string $externalIdentifier
     * @param string $externalRelativeUri
     * @param string $nodeIdentifier
     * @param string $nodePath
     */
    public function set($importerClassName, $externalIdentifier, $externalRelativeUri, $nodeIdentifier, $nodePath)
    {
        $this->importService->addOrUpdateRecordMapping($importerClassName, $externalIdentifier, $externalRelativeUri, $nodeIdentifier, $nodePath);
    }

    /**
     * @param string $importerClassName
     * @param string $externalIdentifier
     * @return RecordMapping
     */
    public function get($importerClassName, $externalIdentifier)
    {
        return $this->recordMappingRepository->findOneByImporterClassNameAndExternalIdentifier($importerClassName, $externalIdentifier);
    }
}
