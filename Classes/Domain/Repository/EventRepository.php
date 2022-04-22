<?php

namespace Ttree\ContentRepositoryImporter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\EventLog\Domain\Repository\EventRepository as NeosEventRepository;

/**
 * @Flow\Scope("singleton")
 */
class EventRepository extends NeosEventRepository
{
    /**
     * Remove all events without checking foreign keys. Needed for clearing the table during tests.
     *
     * @return void
     */
    public function removeAll(): void
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();

        $isMySQL = $connection->getDriver()->getName() === 'pdo_mysql';
        if ($isMySQL) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }

        $connection->query("DELETE FROM neos_neos_eventlog_domain_model_event WHERE dtype = 'ttree_contentrepositoryimporter_event'");

        if ($isMySQL) {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
