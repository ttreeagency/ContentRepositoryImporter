<?php
namespace Ttree\ContentRepositoryImporter\DataType;

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
