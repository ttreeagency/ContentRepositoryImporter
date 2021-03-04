<?php
namespace Ttree\ContentRepositoryImporter\Domain\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Ttree\ContentRepositoryImporter\Domain\Model\RecordMapping;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class RecordMappingRepository extends Repository
{
    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param string $importerClassName
     * @param string $externalIdentifier
     * @return RecordMapping
     */
    public function findOneByImporterClassNameAndExternalIdentifier($importerClassName, $externalIdentifier)
    {
        $query = $this->createQuery();

        $query->matching($query->logicalAnd(
            $query->equals('importerClassNameHash', md5($importerClassName)),
            $query->equals('externalIdentifier', $externalIdentifier)
        ));

        return $query->execute()->getFirst();
    }

    /**
     * Persists all entities managed by the repository and all cascading dependencies
     *
     * @return void
     */
    public function persistEntities()
    {
        foreach ($this->entityManager->getUnitOfWork()->getIdentityMap() as $className => $entities) {
            if ($className === $this->entityClassName) {
                foreach ($entities as $entityToPersist) {
                    $this->entityManager->flush($entityToPersist);
                }
                break;
            }
        }
    }
}
