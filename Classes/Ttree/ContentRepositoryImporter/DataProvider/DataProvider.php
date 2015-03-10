<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * Abstract Data Provider
 */
abstract class DataProvider implements DataProviderInterface {

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $logger;

	/**
	 * @Flow\Inject
	 * @var ProcessedNodeService
	 */
	protected $processedNodeService;

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

	/**
	 * @param integer $offset
	 * @param integer $limit
	 * @return DataProviderInterface
	 */
	public static function create($offset = NULL, $limit = NULL) {
		$class = get_called_class();
		/** @var DataProviderInterface $dataProvider */
		$dataProvider = new $class();
		$dataProvider->setOffset($offset);
		$dataProvider->setLimit($limit);

		return $dataProvider;
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
	 * @param integer $offset
	 */
	public function setOffset($offset) {
		$this->offset = (integer)$offset;
	}

	/**
	 * @param integer $limit
	 */
	public function setLimit($limit) {
		$this->limit = (integer)$limit;
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