<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
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
     * @return QueryBuilder
     * @throws Exception
     */
    protected function createQuery(): QueryBuilder
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
     * @throws Exception
     */
    protected function getDatabaseConnection(): Connection
    {
        $sourceName = isset($this->options['source']) ? $this->options['source'] : 'default';

        if (isset($this->connections[$sourceName]) && $this->connections[$sourceName] instanceof Connection) {
            return $this->connections[$sourceName];
        }

        $this->connections[$sourceName] = DriverManager::getConnection($this->settings['sources'][$sourceName], new Configuration());
        return $this->connections[$sourceName];
    }
}
