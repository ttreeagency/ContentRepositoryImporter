<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use TYPO3\Flow\Annotations as Flow;

/**
 * Abstract Data Provider
 */
abstract class DataProvider implements DataProviderInterface {

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * @Flow\Inject(setting="import")
	 * @var array
	 */
	protected $configuration = [];

	/**
	 * @var integer
	 */
	protected $offset;

	/**
	 * @var integer
	 */
	protected $limit;

	/**
	 * @var integer
	 */
	protected $count = 0;

	function __construct($offset = NULL, $limit = NULL) {
		$this->offset = $offset ? (integer)$offset : NULL;
		$this->limit = $limit ? (integer)$limit : NULL;
	}


	/**
	 * @return \Doctrine\DBAL\Query\QueryBuilder
	 */
	protected function createQuery() {
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
	 * @return integer
	 */
	public function getCount() {
		return $this->count;
	}

	/**
	 * @return boolean
	 */
	public function hasLimit() {
		return $this->limit > 0;
	}

	/**
	 * @return Connection
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function getDatabaseConnection() {
		if ($this->connection instanceof Connection) {
			return $this->connection;
		}

		$this->connection = DriverManager::getConnection($this->configuration['database'], new Configuration());
		return $this->connection;
	}
}