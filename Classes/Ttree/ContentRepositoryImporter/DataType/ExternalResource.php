<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                                  *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentRepositoryImporter". *
 *                                                                                  */

use Gedmo\Uploadable\MimeType\MimeTypeGuesser;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
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
	 * @var VariableFrontend
	 */
	protected $downloadCache;

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
		$overrideFilename = isset($value['overrideFilename']) ? trim($value['overrideFilename']) : $filename;

		if (!isset($this->options['downloadDirectory'])) {
			throw new Exception('Missing download directory data type option', 1425981085);
		}
		Files::createDirectoryRecursively($this->options['downloadDirectory']);
		$temporaryFileAndPathname = trim($this->options['downloadDirectory'] . $filename);

		$this->download($sourceUri, $temporaryFileAndPathname);
		$sha1Hash = sha1_file($temporaryFileAndPathname);

		# Try to add file extenstion if missing
		if (!$this->downloadCache->has($sha1Hash)) {
			$fileExtension = pathinfo($temporaryFileAndPathname, PATHINFO_EXTENSION);
			if (trim($fileExtension) === '') {
				$mimeTypeGuesser = new MimeTypeGuesser();
				$mimeType = $mimeTypeGuesser->guess($temporaryFileAndPathname);
				$this->logger->log(sprintf('Try to guess mime type for "%s" (%s), result: %s', $sourceUri, $filename, $mimeType), LOG_DEBUG);
				$fileExtension = MediaTypes::getFilenameExtensionFromMediaType($mimeType);
				if ($fileExtension !== '') {
					$oldTemporaryDestination = $temporaryFileAndPathname;
					$temporaryDestination = $temporaryFileAndPathname . '.' . $fileExtension;
					copy($oldTemporaryDestination, $temporaryDestination);
					$this->logger->log(sprintf('Rename "%s" to "%s"', $oldTemporaryDestination, $temporaryDestination), LOG_DEBUG);
				}
			}
		}

		$resource = $this->resourceManager->getResourceBySha1($sha1Hash);
		if ($resource === NULL) {
			$resource = $this->resourceManager->importResource($temporaryFileAndPathname);
		}
		if ($filename !== $overrideFilename) {
			$resource->setFilename($overrideFilename);
		}

		$this->temporaryFileAndPathname = $temporaryFileAndPathname;

		$this->downloadCache->set($sha1Hash, [
			'sha1Hash' => $sha1Hash,
			'filename' => $filename,
			'sourceUri' => $sourceUri,
			'temporaryFileAndPathname' => $temporaryFileAndPathname
		]);

		$this->value = $resource;
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

		$ch = curl_init(str_replace(" ","%20",$source));
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);
		curl_close($ch);

		fclose($fp);

		return TRUE;
	}

}