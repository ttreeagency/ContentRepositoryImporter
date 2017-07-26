<?php
namespace Ttree\ContentRepositoryImporter\DataType;

use Neos\Flow\Annotations as Flow;

/**
 * String Data Type
 */
class Integer extends StringValue
{
    /**
     * @return integer
     */
    public function getValue()
    {
        $this->initializeValue($this->rawValue);
        return (integer)$this->value;
    }
}
