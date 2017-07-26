<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\EventLog\Domain\Model\NodeEvent;

/**
 * A specific event used by the ContentRepositoryImporter
 *
 * @Flow\Entity
 */
class Event extends NodeEvent
{
    /**
     * @var string
     */
    protected $externalIdentifier;

    /**
     * @return string
     */
    public function getExternalIdentifier()
    {
        return $this->externalIdentifier;
    }

    /**
     * @param string $externalIdentifier
     */
    public function setExternalIdentifier($externalIdentifier)
    {
        $this->externalIdentifier = $externalIdentifier;
    }
}
