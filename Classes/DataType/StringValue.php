<?php
namespace Ttree\ContentRepositoryImporter\DataType;

use Neos\Flow\Annotations as Flow;

/**
 * String Data Type
 */
class StringValue extends DataType
{
    /**
     * @param string $value
     */
    protected function initializeValue($value)
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = strip_tags($value);
        $this->value = $value;
    }
}
