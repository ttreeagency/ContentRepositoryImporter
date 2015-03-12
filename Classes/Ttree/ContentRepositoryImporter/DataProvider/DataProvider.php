<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

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
	 * @var array<Connection>
	 */
	protected $connections = [];

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
	 * @Flow\InjectConfiguration(package="Ttree.ContentRepositoryImporter")
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @param array $options
	 */
	public function __construct(array $options) {
		$this->options = $options;
	}

	/**
	 * @param array $options
	 * @param integer $offset
	 * @param integer $limit
	 * @return DataProviderInterface
	 */
	public static function create(array $options = [], $offset = NULL, $limit = NULL) {
		$class = get_called_class();
		/** @var DataProviderInterface $dataProvider */
		$dataProvider = new $class($options);
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
	 * @throws \Exception
	 */
	protected function getDatabaseConnection() {
		$sourceName = isset($this->options['source']) ? $this->options['source'] : 'default';

		if (isset($this->connections[$sourceName]) && $this->connections[$sourceName] instanceof Connection) {
			return $this->connections[$sourceName];
		}

		$this->connections[$sourceName] = DriverManager::getConnection($this->settings['sources'][$sourceName], new Configuration());
		return $this->connections[$sourceName];
	}
}