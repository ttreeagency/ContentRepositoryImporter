<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Ttree\ContentRepositoryImporter\Domain\Model\RecordMapping;
use Ttree\ContentRepositoryImporter\Domain\Repository\RecordMappingRepository;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;
use Neos\Flow\Annotations as Flow;

/**
 * Processed Node Service
 *
 * @Flow\Scope("singleton")
 */
class ProviderPropertyValidity
{
    /**
     * @var NodeInterface|NodeTemplate
     */
    private $nodeOrTemplate;

    /**
     * @param NodeInterface|NodeTemplate $nodeOrTemplate
     */
    public function __construct($nodeOrTemplate)
    {
        $this->nodeOrTemplate = $nodeOrTemplate;
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    public function isValid($propertyName)
    {
        $availableProperties = $this->nodeOrTemplate->getNodeType()->getProperties();
        return !(\in_array(substr($propertyName, 0, 1), ['_', '@']) || !isset($availableProperties[$propertyName]));
    }
}
