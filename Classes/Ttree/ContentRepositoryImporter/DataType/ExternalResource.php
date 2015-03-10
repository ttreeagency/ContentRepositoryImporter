<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

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

		if (!isset($this->options['downloadDirectory'])) {
			throw new Exception('Missing download directory data type option', 1425981085);
		}
		Files::createDirectoryRecursively($this->options['downloadDirectory']);
		$temporaryFileAndPathname = trim($this->options['downloadDirectory'] . $filename);
		$sha1Hash = sha1_file($temporaryFileAndPathname);

		# Try to add file extenstion if missing
		if (!$this->downloadCache->has($sha1Hash)) {
			$this->download($sourceUri, $temporaryFileAndPathname);
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
		$rh = fopen($source, 'rb');
		$wh = fopen($destination, 'w+b');
		if (!$rh || !$wh) {
			throw new Exception(sprintf('Unable to download the given file: %s', $source));
		}

		while (!feof($rh)) {
			if (fwrite($wh, fread($rh, 4096)) === FALSE) {
				throw new Exception(sprintf('Unable to download the given file: %s', $source));
			}
		}

		fclose($rh);
		fclose($wh);

		return TRUE;
	}

}