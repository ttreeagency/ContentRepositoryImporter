<?php
namespace Ttree\ContentRepositoryImporter\DataProvider;

use Ttree\ContentRepositoryImporter\Exception\InvalidArgumentException;
use Ttree\ContentRepositoryImporter\Service\ProcessedNodeService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;

/**
 * Csv Data Provider
 */
class CsvDataProvider extends AbstractDataProvider
{
    /**
     * @Flow\Inject
     * @var ProcessedNodeService
     */
    protected $processedNodeService;

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
     * Initialize this data provider with the currently set options
     *
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
            $this->csvEnclosure = $this->options['csvEnclosure'];
        }

        $this->logger->log(sprintf('%s will read from "%s", using %s as delimiter and %s as enclosure character.', get_class($this), $this->csvFilePath, $this->csvDelimiter, $this->csvEnclosure), LOG_DEBUG);
    }

    /**
     * Fetch all the data from this Data Source.
     *
     * If offset and / or limit are set, only those records will be returned.
     *
     * @return array The records
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function fetch()
    {
        static $currentLine = 0;
        $dataResult = array();

        if (isset($this->options['skipHeader']) && $this->options['skipHeader'] === true) {
            $skipLines = 1;
        } elseif (isset($this->options['skipHeader']) && \is_numeric($this->options['skipHeader'])) {
            $skipLines = (int)$this->options['skipHeader'];
        } else {
            $skipLines = 0;
        }
        if (($handle = fopen($this->csvFilePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 65534, $this->csvDelimiter, $this->csvEnclosure)) !== false) {
                // skip header (maybe is better to set the first offset position instead)
                if ($currentLine < $skipLines) {
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
     * Can be use to process data in children class
     *
     * @param array $data
     */
    protected function preProcessRecordData(&$data)
    {
    }

}
