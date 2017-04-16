<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

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
