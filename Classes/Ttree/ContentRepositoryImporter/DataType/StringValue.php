<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use TYPO3\Flow\Annotations as Flow;

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
