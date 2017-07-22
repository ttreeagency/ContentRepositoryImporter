<?php
namespace Ttree\ContentRepositoryImporter\DataType;

use Neos\Flow\Annotations as Flow;

/**
 * Data Type Interface
 */
interface DataTypeInterface
{
    /**
     * @param mixed $value
     * @return $this
     */
    public static function create($value);

    /**
     * @return mixed
     */
    public function getValue();
}
