<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * String Data Type
 */
abstract class DataType implements DataTypeInterface {

	/**
	 * @var mixed
	 */
	protected $rawValue;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @param string $value
	 */
	public function __construct($value) {
		$this->rawValue = $value;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		$this->initializeValue($this->rawValue);
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return $this
	 */
	public static function create($value) {
		$class = get_called_class();
		return new $class($value);
	}

	/**
	 * @param mixed $value
	 */
	protected function initializeValue($value) {
		$this->value = $value;
	}


}