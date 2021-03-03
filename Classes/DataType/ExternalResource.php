<?php
namespace Ttree\ContentRepositoryImporter\DataType;

use Gedmo\Uploadable\MimeType\MimeTypeGuesser;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\Files;
use Neos\Utility\MediaTypes;
use Psr\Log\LoggerInterface;

/**
 * String Data Type
 */
class ExternalResource extends DataType
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var string
     */
    protected $temporaryFileAndPathname;

    /**
     * @return Resource
     */
    public function getValue()
    {
        return parent::getValue();
    }

    /**
     * Enable force download
     */
    public function enableForceDownload()
    {
        $this->rawValue['forceDownload'] = true;
    }

    /**
     * Enable force download
     */
    public function disableForceDownload()
    {
        $this->rawValue['forceDownload'] = false;
    }

    /**
     * @param string $value
     * @throws Exception
     * @throws \Neos\Flow\ResourceManagement\Exception
     * @throws \Neos\Flow\Utility\Exception
     */
    protected function initializeValue($value)
    {
        if (!is_array($value)) {
            throw new Exception('Value must be an array, with source URI (sourceUri) and filename (filename)', 1425981082);
        }
        if (!isset($value['sourceUri'])) {
            throw new Exception('Missing source URI', 1425981083);
        }
        $sourceUri = trim($value['sourceUri']);
        if (!isset($value['filename'])) {
            throw new Exception('Missing filename URI', 1425981084);
        }
        $filename = trim($value['filename']);
        $fileExtension = strtolower(trim(pathinfo($filename, PATHINFO_EXTENSION)));
        $overrideFilename = isset($value['overrideFilename']) ? trim($value['overrideFilename']) : pathinfo($filename, PATHINFO_FILENAME);
        if ($fileExtension) {
            $overrideFilename = sprintf('%s.%s', $overrideFilename, $fileExtension);
        }

        if (!isset($this->options['downloadDirectory'])) {
            throw new Exception('Missing download directory data type option', 1425981085);
        }
        Files::createDirectoryRecursively($this->options['downloadDirectory']);

        $temporaryFilename = isset($value['temporaryPrefix']) ? trim(sprintf('%s-%s', $value['temporaryPrefix'], $overrideFilename)) : trim($overrideFilename);
        $temporaryFileAndPathname = sprintf('%s%s', $this->options['downloadDirectory'], $temporaryFilename);

        $username = isset($value['username']) ? $value['username'] : null;
        $password = isset($value['password']) ? $value['password'] : null;
        $this->download($sourceUri, $temporaryFileAndPathname, isset($value['forceDownload']) ? (boolean)$value['forceDownload'] : false, $username, $password);

        # Try to add file extenstion if missing
        if ($fileExtension === '') {
            $mimeTypeGuesser = new MimeTypeGuesser();
            $mimeType = $mimeTypeGuesser->guess($temporaryFileAndPathname);
            $this->logger->debug(sprintf('Try to guess mime type for "%s" (%s), result: %s', $sourceUri, $filename, $mimeType));
            $fileExtension = MediaTypes::getFilenameExtensionFromMediaType($mimeType);
            if ($fileExtension !== '') {
                $oldTemporaryDestination = $temporaryFileAndPathname;
                $temporaryFileAndPathname = sprintf('%s.%s', $temporaryFileAndPathname, $fileExtension);
                if (!is_file($temporaryFileAndPathname)) {
                    copy($oldTemporaryDestination, $temporaryFileAndPathname);
                    $this->logger->debug(sprintf('Rename "%s" to "%s"', $oldTemporaryDestination, $temporaryFileAndPathname));
                }
            }
        }
        # Trim border
        if (isset($value['trimBorder']) && $value['trimBorder'] === true) {
            $this->trimImageBorder($temporaryFileAndPathname);
        }

        $sha1Hash = sha1_file($temporaryFileAndPathname);
        $resource = $this->resourceManager->getResourceBySha1($sha1Hash);
        if ($resource === null) {
            $this->logger->debug('Import new resource');
            $resource = $this->resourceManager->importResource($temporaryFileAndPathname);
            $resource->setFilename(basename($temporaryFileAndPathname));
        } else {
            $this->logger->debug('Use existing resource');
        }

        $this->temporaryFileAndPathname = $temporaryFileAndPathname;

        $this->value = $resource;
    }

    /**
     * @param string $fileAndPathname
     */
    protected function trimImageBorder($fileAndPathname)
    {
        $isProcessed = sprintf('%s.trimmed', $fileAndPathname);
        if (is_file($isProcessed)) {
            return;
        }
        $isImage = @getimagesize($fileAndPathname) ? true : false;
        if (!$isImage) {
            return;
        }
        $command = sprintf('convert "%s" -trim "%s" > /dev/null 2> /dev/null', $fileAndPathname, $fileAndPathname);
        exec($command, $output, $result);
        touch($isProcessed);
    }

    /**
     * @return string
     */
    public function getTemporaryFileAndPathname()
    {
        return $this->temporaryFileAndPathname;
    }

    /**
     * @return string
     */
    public function getSourceUri()
    {
        return $this->rawValue['sourceUri'];
    }

    /**
     * @param string $source
     * @param string $destination
     * @param boolean $force
     * @param string|null $username
     * @param string|null $password
     * @return boolean
     * @throws Exception
     */
    protected function download($source, $destination, $force = false, $username = null, $password = null)
    {
        if ($force === false && is_file($destination)) {
            $this->logger->debug(sprintf('External resource "%s" skipped, local file "%s" exist', $source, $destination));
            return true;
        }
        $fp = fopen($destination, 'w');
        if (!$fp) {
            throw new Exception(sprintf('Unable to download the given file: %s', $source));
        }

        $ch = curl_init(str_replace(" ", "%20", $source));
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($username !== null && $password !== null) {
            curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $username, $password));
        }
        curl_exec($ch);
        curl_close($ch);

        fclose($fp);

        $this->logger->debug(sprintf('External resource "%s" downloaded to "%s"', $source, $destination));

        return true;
    }
}
