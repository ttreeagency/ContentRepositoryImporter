<?php
namespace Ttree\ContentRepositoryImporter\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Utility\Arrays;
use Ttree\ContentRepositoryImporter\Domain\Model\Event;
use Ttree\ContentRepositoryImporter\Domain\Model\ProviderPropertyValidity;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class DimensionsImporter
{
    /**
     * @var NodePropertyMapper
     * @Flow\Inject
     */
    protected $nodePropertyMapper;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="dimensionsImporter.presets")
     */
    protected $settings;

    /**
     * @param NodeInterface $node
     * @param array $data
     * @param Event $event
     */
    public function process(NodeInterface $node, array $data, Event $event)
    {
        if (!isset($data['@dimensions'])) {
            return;
        }
        $propertyValidity = new ProviderPropertyValidity($node);
        $dataFilter = function($propertyName) use ($propertyValidity) {
            return $propertyValidity->isValid($propertyName);
        };
        $dimensionsData = $data['@dimensions'];
        $properties = \array_filter($data, $dataFilter, \ARRAY_FILTER_USE_KEY);

        $contextSwitcher = new ContextSwitcher($node);
        foreach (array_keys($dimensionsData) as $preset) {
            $dimensions = $this->settings[\str_replace('@', '', $preset)];
            $nodeInContext = $contextSwitcher->to($dimensions);
            $localProperties = \array_filter($dimensionsData[$preset], $dataFilter, \ARRAY_FILTER_USE_KEY);
            $localProperties = Arrays::arrayMergeRecursiveOverrule($properties, $localProperties);
            $this->nodePropertyMapper->map($localProperties, $nodeInContext, $event);
        }
    }
}
