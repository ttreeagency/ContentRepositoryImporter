<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

use Cocur\Slugify\Slugify;
use Neos\Flow\Annotations as Flow;

/**
 * String Data Type
 */
class Slug extends DataType
{
    protected $value;

    /**
     * @param string $value
     */
    protected function initializeValue($value)
    {
        $value = new StringValue($value);
        $slugify = new Slugify();
        $this->value = $slugify->slugify($value->getValue());
    }
}
