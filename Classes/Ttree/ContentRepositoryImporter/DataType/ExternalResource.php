<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Gedmo\Uploadable\MimeType\MimeTypeGuesser;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\MediaTypes;

/**
 * String Data Type
 */
class ExternalResource extends DataType {

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
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
	public function getValue() {
		return parent::getValue();
	}

	/**
	 * @param string $value
	 * @throws Exception
	 * @throws \TYPO3\Flow\Resource\Exception
	 * @throws \TYPO3\Flow\Utility\Exception
	 */
	protected function initializeValue($value) {
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

		$temporaryFilename = isset($value['temporaryPrefix']) ? trim(sprintf('%s-%s', $value['temporaryPrefix'], $overrideFilename)) : trim(sprintf('%s%s', $overrideFilename));
		$temporaryFileAndPathname = sprintf('%s%s', $this->options['downloadDirectory'], $temporaryFilename);
		$this->download($sourceUri, $temporaryFileAndPathname);

		# Try to add file extenstion if missing
		if ($fileExtension === '') {
			$mimeTypeGuesser = new MimeTypeGuesser();
			$mimeType = $mimeTypeGuesser->guess($temporaryFileAndPathname);
			$this->logger->log(sprintf('Try to guess mime type for "%s" (%s), result: %s', $sourceUri, $filename, $mimeType), LOG_DEBUG);
			$fileExtension = MediaTypes::getFilenameExtensionFromMediaType($mimeType);
			if ($fileExtension !== '') {
				$oldTemporaryDestination = $temporaryFileAndPathname;
				$temporaryFileAndPathname = sprintf('%s.%s', $temporaryFileAndPathname, $fileExtension);
				if (!is_file($temporaryFileAndPathname)) {
					copy($oldTemporaryDestination, $temporaryFileAndPathname);
					$this->logger->log(sprintf('Rename "%s" to "%s"', $oldTemporaryDestination, $temporaryFileAndPathname), LOG_DEBUG);
				}
			}
		}
		# Trim border
		if (isset($value['trimBorder']) && $value['trimBorder'] === TRUE) {
			$this->trimImageBorder($temporaryFileAndPathname);
		}

		$sha1Hash = sha1_file($temporaryFileAndPathname);
		$resource = $this->resourceManager->getResourceBySha1($sha1Hash);
		if ($resource === NULL) {
			$resource = $this->resourceManager->importResource($temporaryFileAndPathname);
			$resource->setFilename(basename($temporaryFileAndPathname));
		}

		$this->temporaryFileAndPathname = $temporaryFileAndPathname;

		$this->value = $resource;
	}

	/**
	 * @param string $fileAndPathname
	 */
	protected function trimImageBorder($fileAndPathname) {
		$isProcessed = sprintf('%s.trimmed', $fileAndPathname);
		if (is_file($isProcessed)) {
			return;
		}
		$isImage = @getimagesize($fileAndPathname) ? TRUE : FALSE;
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
	public function getTemporaryFileAndPathname() {
		return $this->temporaryFileAndPathname;
	}

	/**
	 * @return string
	 */
	public function getSourceUri() {
		return $this->rawValue['sourceUri'];
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param boolean $force
	 * @return boolean
	 * @throws Exception
	 */
	protected function download($source, $destination, $force = FALSE) {
		if ($force === FALSE && is_file($destination)) {
			return TRUE;
		}
		$fp = fopen($destination, 'w+');
		if (!$fp) {
			throw new Exception(sprintf('Unable to download the given file: %s', $source));
		}

		$ch = curl_init(str_replace(" ", "%20", $source));
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);
		curl_close($ch);

		fclose($fp);

		return TRUE;
	}

}