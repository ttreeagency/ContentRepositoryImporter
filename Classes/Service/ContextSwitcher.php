<?php
namespace Ttree\ContentRepositoryImporter\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Ttree\ContentRepositoryImporter\Domain\Model\ProviderPropertyValidity;
use Neos\Flow\Annotations as Flow;

class ContextSwitcher
{
    /**
     * @var NodeInterface
     */
    private $node;

    public function __construct(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * @param array $dimensions
     * @return NodeInterface|null
     */
    public function to(array $dimensions)
    {
        return (new FlowQuery([$this->node]))->context([
            'dimensions' => $dimensions,
            'targetDimensions' => array_map(function ($dimensionValues) {
                return array_shift($dimensionValues);
            }, $dimensions)
        ])->get(0);
    }
}
