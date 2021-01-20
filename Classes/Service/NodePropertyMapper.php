<?php
namespace Ttree\ContentRepositoryImporter\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Model\ProviderPropertyValidity;
use Neos\Flow\Annotations as Flow;
use Ttree\ContentRepositoryImporter\Domain\Service\ImportService;

/**
 * @Flow\Scope("singleton")
 */
class NodePropertyMapper
{
    /**
     * @Flow\Inject
     * @var ImportService
     */
    protected $importService;

    /**
     * @Flow\InjectConfiguration("eventLog.recordLogEnabled")
     * @var boolean
     */
    protected $recordLogEnabled;

    /**
     * Applies the given properties ($data) to the given Node or NodeTemplate
     *
     * @param array $data Property names and property values
     * @param NodeInterface|NodeTemplate $nodeOrTemplate The Node or Node Template
     * @param Event $currentEvent
     * @return bool True if an existing node has been modified, false if the new properties are the same like the old ones
     */
    public function map(array $data, $nodeOrTemplate, Event $currentEvent = null)
    {
        if (!$nodeOrTemplate instanceof NodeInterface && !$nodeOrTemplate instanceof NodeTemplate) {
            throw new \InvalidArgumentException(sprintf('$nodeOrTemplate must be either an object implementing NodeInterface or a NodeTemplate, %s given.', (is_object($nodeOrTemplate) ? get_class($nodeOrTemplate) : gettype($nodeOrTemplate))), 1462958554616);
        }

        $nodeChanged = false;
        $propertyValidity = new ProviderPropertyValidity($nodeOrTemplate);
        foreach ($data as $propertyName => $propertyValue) {
            if (!$propertyValidity->isValid($propertyName)) {
                continue;
            }
            if ($nodeOrTemplate->getProperty($propertyName) != $propertyValue) {
                $nodeOrTemplate->setProperty($propertyName, $propertyValue);
                $nodeChanged = true;
            }
        }

        if (isset($data['__identifier']) && \is_string($data['__identifier']) && $nodeOrTemplate instanceof NodeTemplate) {
            $nodeOrTemplate->setIdentifier(trim($data['__identifier']));
        }

        if ($nodeOrTemplate instanceof NodeInterface && $this->recordLogEnabled) {
            $path = $nodeOrTemplate->getContextPath();
            if ($nodeChanged) {
                $this->importService->addEventMessage('Node:Processed:Updated', sprintf('Updating existing node "%s" %s (%s)', $nodeOrTemplate->getLabel(), $path, $nodeOrTemplate->getIdentifier()), \LOG_INFO, $currentEvent);
            } else {
                $this->importService->addEventMessage('Node:Processed:Skipped', sprintf('Skipping unchanged node "%s" %s (%s)', $nodeOrTemplate->getLabel(), $path, $nodeOrTemplate->getIdentifier()), \LOG_NOTICE, $currentEvent);
            }
        }

        return $nodeChanged;
    }
}
