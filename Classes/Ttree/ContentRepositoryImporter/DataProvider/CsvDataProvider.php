<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Ttree\ContentRepositoryImporter\Exception\InvalidArgumentException;
use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * Csv Data Provider
 */
class CsvDataProvider implements DataProviderInterface
{
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
     * @var integer
     */
    protected $offset;

    /**
     * @var integer
     */
    protected $limit;

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
     * @var string
     */
    protected $csvFilePath;

    /**
     * @var string
     */
    protected $csvDelimiter = ',';

    /**
     * @var string
     */
    protected $csvEnclosure = '"';

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function initializeObject()
    {
        if (!isset($this->options['csvFilePath']) || !is_string($this->options['csvFilePath'])) {
            throw new InvalidArgumentException('Missing or invalid "csvFilePath" in preset part settings', 1429027715);
        }

        $this->csvFilePath = $this->options['csvFilePath'];
        if (!is_file($this->csvFilePath)) {
            throw new \Exception(sprintf('File "%s" not found', $this->csvFilePath), 1427882078);
        }

        if (isset($this->options['csvDelimiter'])) {
            $this->csvDelimiter = $this->options['csvDelimiter'];
        }

        if (isset($this->options['csvEnclosure'])) {
            $this->csvDelimiter = $this->options['csvEnclosure'];
        }

        $this->logger->log(sprintf('%s will read from "%s", using %s as delimiter and %s as enclosure character.', get_class($this), $this->csvFilePath, $this->csvDelimiter, $this->csvEnclosure), LOG_DEBUG);
    }

    /**
     * @return array
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function fetch()
    {
        static $currentLine = 0;
        $dataResult = array();

        if (($handle = fopen($this->csvFilePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 65534, $this->csvDelimiter, $this->csvEnclosure)) !== false) {
                // skip header (maybe is better to set the first offset position instead)
                if (isset($this->options['skipHeader']) && $this->options['skipHeader'] === true && $currentLine === 0) {
                    $currentLine++;
                    continue;
                }
                if ($currentLine >= $this->offset && $currentLine < ($this->offset + $this->limit)) {
                    if (isset($data[0]) && $data[0] !== '') {
                        $this->preProcessRecordData($data);
                        $dataResult[] = $data;
                    }
                }
                $currentLine++;
            }
            fclose($handle);
        }

        $this->logger->log(sprintf('%s: read %s lines and found %s records.', $this->csvFilePath, $currentLine, count($dataResult)), LOG_DEBUG);
        return $dataResult;
    }

    /**
     * @param array $options
     * @param integer $offset
     * @param integer $limit
     * @return DataProviderInterface
     */
    public static function create(array $options = [], $offset = null, $limit = null)
    {
        $class = get_called_class();
        /** @var DataProviderInterface $dataProvider */
        $dataProvider = new $class($options);
        $dataProvider->setOffset($offset);
        $dataProvider->setLimit($limit);

        return $dataProvider;
    }

    /**
     * @param integer $offset
     */
    public function setOffset($offset)
    {
        $this->offset = (integer)$offset;
    }

    /**
     * @param integer $limit
     */
    public function setLimit($limit)
    {
        $this->limit = (integer)$limit;
    }

    /**
     * @return boolean
     */
    public function hasLimit()
    {
        return $this->limit > 0;
    }

    /**
     * Can be use to process data in children class
     * @param array $data
     */
    protected function preProcessRecordData(&$data)
    {
    }
}
