<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * Abstract Data Provider
 *
 * @api
 */
abstract class AbstractDataProvider implements DataProviderInterface
{
    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     * @api
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ProcessedNodeService
     * @api
     */
    protected $processedNodeService;

    /**
     * @var integer
     * @api
     */
    protected $offset;

    /**
     * @var integer
     * @api
     */
    protected $limit;

    /**
     * @Flow\InjectConfiguration(package="Ttree.ContentRepositoryImporter")
     * @var array
     * @api
     */
    protected $settings;

    /**
     * @var array
     * @api
     */
    protected $options = [];

    /**
     * @param array $options
     * @api
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Factory method which returns an instance of the concrete implementation and passes the given options, offset
     * and limit to it.
     *
     * @param array $options Options for the Data Provider
     * @param integer $offset Record offset where the import should start
     * @param integer $limit Maximum number of records which should be imported
     * @return DataProviderInterface
     */
    public static function create(array $options = [], $offset = null, $limit = null)
    {
        $dataProvider = new static($options);
        $dataProvider->setOffset($offset);
        $dataProvider->setLimit($limit);

        return $dataProvider;
    }

    /**
     * Set the offset (record number) to start importing at
     *
     * @param integer $offset
     */
    public function setOffset($offset)
    {
        $this->offset = (integer)$offset;
    }

    /**
     * Set the maximum number of records to import
     *
     * @param integer $limit
     */
    public function setLimit($limit)
    {
        $this->limit = (integer)$limit;
    }

    /**
     * If a maximum number of records has been set
     *
     * @return boolean
     */
    public function hasLimit()
    {
        return $this->limit > 0;
    }

}
