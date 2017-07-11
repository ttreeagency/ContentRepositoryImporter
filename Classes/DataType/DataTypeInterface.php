<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*
 * This script belongs to the Neos Flow package "Ttree.ContentRepositoryImporter".
 */

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
