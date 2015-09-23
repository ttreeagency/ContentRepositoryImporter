<?php
namespace Ttree\ContentRepositoryImporter\Domain\Repository;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EventRepository extends \TYPO3\Neos\EventLog\Domain\Repository\EventRepository
{
    /**
     * Remove all events without checking foreign keys. Needed for clearing the table during tests.
     *
     * @return void
     */
    public function removeAll()
    {
        $connection = $this->entityManager->getConnection();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $connection->query('DELETE FROM typo3_neos_eventlog_domain_model_event WHERE dtype = "ttree_contentrepositoryimporter_event"');
        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
