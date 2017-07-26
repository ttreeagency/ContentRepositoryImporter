<?php
namespace Ttree\ContentRepositoryImporter\DataType;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;

/**
 * String Data Type
 */
abstract class DataType implements DataTypeInterface
{
    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var mixed
     */
    protected $rawValue;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var array
     */
    protected $options;

    /**
     * Initialize object
     */
    public function initializeObject()
    {
        $this->options = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, [ 'Ttree', 'ContentRepositoryImporter', 'dataTypeOptions', get_called_class()]) ?: array();
    }

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->rawValue = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $this->initializeValue($this->rawValue);
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public static function create($value)
    {
        $class = get_called_class();
        return new $class($value);
    }

    /**
     * @param mixed $value
     */
    protected function initializeValue($value)
    {
        $this->value = $value;
    }
}
