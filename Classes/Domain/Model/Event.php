<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

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
