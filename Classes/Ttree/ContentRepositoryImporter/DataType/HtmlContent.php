<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;

/**
 * String Data Type
 */
class HtmlContent extends DataType
{
    protected $value;

    /**
     * @param string $value
     */
    protected function initializeValue($value)
    {
        $value = trim($value);

        $config = \HTMLPurifier_Config::createDefault();
        $options = Arrays::getValueByPath($this->options, 'htmlPurifierOptions') ?: [];
        foreach ($options as $optionName => $optionValue) {
            $config->set($optionName, $optionValue);
        }
        $purifier = new \HTMLPurifier($config);
        $value = $purifier->purify($value);

        // Todo add options in data type settings
        $value = str_replace([
            '<ul>',
            '</ul>',
            '<ol>',
            '</ol>',
            '&nbsp;'
        ], [
            PHP_EOL . '<ul>' . PHP_EOL,
            PHP_EOL . '</ul>' . PHP_EOL,
            PHP_EOL . '<ol>' . PHP_EOL,
            PHP_EOL . '</ol>' . PHP_EOL,
            ' '
        ], $value);

        // Normalize tag
        $options = Arrays::getValueByPath($this->options, 'preProcessing') ?: [];
        if (count($options)) {
            $value = preg_replace(array_keys($options), array_values($options), $value);
        }

        // Normalize line break
        $value = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', "\n", $value);

        // Process line per line
        $lines = preg_split("/\\r\\n|\\r|\\n/", $value);
        foreach ($lines as $key => $line) {
            $line = trim($line);
            $options = Arrays::getValueByPath($this->options, 'processingPerLine') ?: [];
            if (count($options)) {
                $lines[$key] = preg_replace(array_keys($options), array_values($options), $line);
            }
        }
        $value = implode(' ' . PHP_EOL, $lines);

        // Global post processing
        $options = Arrays::getValueByPath($this->options, 'postProcessing') ?: [];
        if (count($options)) {
            $value = preg_replace(array_keys($options), array_values($options), $value);
        }

        // Return everything on one line
        $value = str_replace(PHP_EOL, '', $value);

        // Remove duplicated space and trim
        $this->value = trim(preg_replace('/\s+/u', ' ', $value));
    }
}
