<?php
namespace Ttree\ContentRepositoryImporter\Domain\Model;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Flow\Entity
 * @ORM\Table(
 *  uniqueConstraints={
 * 		@ORM\UniqueConstraint(name="importerclassnamehash_externalidentifier",columns={"importerclassnamehash", "externalidentifier"})
 * 	},
 * 	indexes={
 * 		@ORM\Index(name="importerclassnamehash_externalidentifier",columns={"importerclassnamehash", "externalidentifier"}),
 * 		@ORM\Index(name="nodepathhash",columns={"nodepathhash"}),
 * 		@ORM\Index(name="nodeidentifier",columns={"nodeidentifier"})
 * 	}
 * )
 */
class RecordMapping
{
    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     */
    protected $creationDate;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="change", field={"externalRelativeUri", "nodeIdentifier", "nodePath"})
     * @ORM\Column(nullable=true)
     */
    protected $modificationDate;

    /**
     * @var string
     */
    protected $importerClassName;

    /**
     * @var string
     * @ORM\Column(length=32)
     */
    protected $importerClassNameHash;

    /**
     * @var string
     */
    protected $externalIdentifier;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $externalRelativeUri;

    /**
     * @var string
     */
    protected $nodeIdentifier;

    /**
     * @var string
     * @ORM\Column(length=4000)
     */
    protected $nodePath;

    /**
     * @var string
     * @ORM\Column(length=32)
     */
    protected $nodePathHash;

    /**
     * @param string $importerClassName
     * @param string $externalIdentifier
     * @param string $externalRelativeUri
     * @param string $nodeIdentifier
     * @param string $nodePath
     */
    public function __construct($importerClassName, $externalIdentifier, $externalRelativeUri, $nodeIdentifier, $nodePath)
    {
        $this->importerClassName = $importerClassName;
        $this->importerClassNameHash = md5($importerClassName);
        $this->externalIdentifier = $externalIdentifier;
        $this->externalRelativeUri = $externalRelativeUri;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodePath = $nodePath;
        $this->nodePathHash = md5($nodePath);
    }

    /**
     * @return string
     */
    public function getImporterClassName()
    {
        return $this->importerClassName;
    }

    /**
     * @return string
     */
    public function getExternalIdentifier()
    {
        return $this->externalIdentifier;
    }

    /**
     * @return string
     */
    public function getExternalRelativeUri()
    {
        return $this->externalRelativeUri;
    }

    /**
     * @param string $externalRelativeUri
     */
    public function setExternalRelativeUri($externalRelativeUri)
    {
        $this->externalRelativeUri = $externalRelativeUri;
    }

    /**
     * @return string
     */
    public function getNodeIdentifier()
    {
        return $this->nodeIdentifier;
    }

    /**
     * @param string $nodeIdentifier
     */
    public function setNodeIdentifier($nodeIdentifier)
    {
        $this->nodeIdentifier = $nodeIdentifier;
    }

    /**
     * @return string
     */
    public function getNodePath()
    {
        return $this->nodePath;
    }

    /**
     * @param string $nodePath
     */
    public function setNodePath($nodePath)
    {
        $this->nodePath = $nodePath;
        $this->nodePathHash = md5($nodePath);
    }
}
