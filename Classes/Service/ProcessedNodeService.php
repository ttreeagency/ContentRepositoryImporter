<?php
namespace Ttree\ContentRepositoryImporter\Service;

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
     * @param string $presetPath
     */
    public function set($importerClassName, $externalIdentifier, $externalRelativeUri, $nodeIdentifier, $nodePath, $presetPath)
    {
        $this->importService->addOrUpdateRecordMapping($this->buildImporterClassName($importerClassName, $presetPath), $externalIdentifier, $externalRelativeUri, $nodeIdentifier, $nodePath);
    }

    /**
     * @param string $importerClassName
     * @param string $externalIdentifier
     * @return RecordMapping
     */
    public function get($importerClassName, $externalIdentifier, $presetPath)
    {
        return $this->recordMappingRepository->findOneByImporterClassNameAndExternalIdentifier($this->buildImporterClassName($importerClassName, $presetPath), $externalIdentifier);
    }

    protected function buildImporterClassName($importerClassName, $presetPath)
    {
        return $importerClassName . '@' . $presetPath;
    }
}
