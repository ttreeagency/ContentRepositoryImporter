<?php
namespace Ttree\ContentRepositoryImporter\DataType;

use Neos\Flow\Annotations as Flow;

/**
 * Date Data Type
 */
class Date extends DataType
{

    protected function initializeValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            // $value is already a DateTime object, use it directly
            $this->value = clone $value;
        } elseif (is_numeric($value)) {
            // $value is UNIX timestamp
            if ($timestamp = (int)$value) {
                $this->value = new \DateTime();
                $this->value->setTimestamp($timestamp);
            } else {
                $this->value = null;
            }
        } elseif (is_string($value)) {
            // fallback, try to parse $value as a date string
            $this->value = new \DateTime($value);
        } else {
            throw new \Exception(sprintf('Cannot convert %s to a DateTime object', $value));
        }
    }

    /**
     * Initialize a date value from input
     *
     * Input value can be a date/time string, parsable by the PHP DateTime class,
     * or a numeric value (int or string), which then will be interpreted as a UNIX
     * timestamp in the local configured TZ.
     *
     */
    public static function create($value)
    {
        return parent::create($value);
    }

    /**
     * Gets the DateTime value or null, if the datetime was initialized from null or
     * an empty timestamp.
     *
     * @return \DateTime | null
     */
    public function getValue()
    {
        return parent::getValue();
    }
}
