<?php
namespace Ttree\ContentRepositoryImporter\Importer;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Ttree\ContentRepositoryImporter\DataType\Slug;
use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;

/**
 * A base importer for data providers which deliver data as commands (create, delete, update, ...) instead of plain
 * data records.
 */
abstract class AbstractCommandBasedImporter extends AbstractImporter
{

    /**
     * Processes a single command
     *
     * Override this method if you need a different approach.
     *
     * @param NodeTemplate $nodeTemplate
     * @param array $data
     * @return void
     * @throws \Exception
     * @api
     */
    public function processRecord(NodeTemplate $nodeTemplate, array $data)
    {
        $this->unsetAllNodeTemplateProperties($nodeTemplate);

        $externalIdentifier = $this->getExternalIdentifierFromRecordData($data);
        if (!isset($data['uriPathSegment'])) {
            $data['uriPathSegment'] = Slug::create($this->getLabelFromRecordData($data))->getValue();
        }

        $this->nodeTemplate->setNodeType($this->nodeType);
        $this->nodeTemplate->setName($this->renderNodeName($externalIdentifier));

        if (!isset($data['mode'])) {
            throw new \Exception(sprintf('Could not determine command mode from data record with external identifier %s. Please make sure that "mode" exists in that record.', $externalIdentifier), 1462985246103);
        }

        $commandMethodName = $data['mode'] . 'Command';
        if (!method_exists($this, $commandMethodName)) {
            throw new \Exception(sprintf('Could not find a command method "%s" in %s for processing record with external identifier %s.', $commandMethodName, get_class($this), $externalIdentifier), 1462985425892);
        }

        $this->$commandMethodName($externalIdentifier, $data);
    }

}
