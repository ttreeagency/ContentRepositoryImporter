<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

use Neos\Cache\Frontend\VariableFrontend;
use Psr\Log\LoggerInterface;
use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use Neos\Flow\Annotations as Flow;
use Ttree\ContentRepositoryImporter\Service\Vault;

/**
 * Abstract Data Provider
 *
 * @api
 */
abstract class AbstractDataProvider implements DataProviderInterface
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
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
     * @var Vault
     */
    protected $vault;

    /**
     * @var integer
     * @api
     */
    protected $offset = 0;

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
     * @var string
     */
    protected $presetName;

    /**
     * @var string
     */
    protected $partName;

    /**
     * @param array $options
     * @param Vault|null $vault
     * @api
     */
    public function __construct(array $options, Vault $vault)
    {
        $this->options = $options;
        $this->presetName = $options['__presetName'];
        $this->partName = $options['__partName'];
        $this->vault = $vault;
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
    public static function create(array $options = [], $offset = null, $limit = null): DataProviderInterface
    {
        $dataProvider = new static($options, new Vault($options['__presetName']));
        $dataProvider->setOffset($offset);
        $dataProvider->setLimit($limit);

        return $dataProvider;
    }

    /**
     * Set the offset (record number) to start importing at
     *
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = (int)$offset;
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
