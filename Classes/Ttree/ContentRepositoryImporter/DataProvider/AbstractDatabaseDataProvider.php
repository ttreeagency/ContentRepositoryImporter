<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Neos\Flow\Annotations as Flow;

/**
 * Database based Data Provider
 */
abstract class AbstractDatabaseDataProvider extends AbstractDataProvider
{
    /**
     * @var array<Connection>
     */
    protected $connections = [];

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function createQuery()
    {
        $query = $this->getDatabaseConnection()
            ->createQueryBuilder();

        if ($this->limit > 0) {
            $query
                ->setFirstResult($this->offset ?: 0)
                ->setMaxResults($this->limit);
        }

        return $query;
    }

    /**
     * @return Connection
     * @throws \Exception
     */
    protected function getDatabaseConnection()
    {
        $sourceName = isset($this->options['source']) ? $this->options['source'] : 'default';

        if (isset($this->connections[$sourceName]) && $this->connections[$sourceName] instanceof Connection) {
            return $this->connections[$sourceName];
        }

        $this->connections[$sourceName] = DriverManager::getConnection($this->settings['sources'][$sourceName], new Configuration());
        return $this->connections[$sourceName];
    }
}
