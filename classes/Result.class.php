<?php

/**
 * Result
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Result
 *
 * Class that represents a result from a method when it needs to provide complex results
 *
 * @package Cherrycake
 * @category Classes
 */
class Result extends BasicObject {
	protected $isOk;
	protected $payload;

	/**
	 * Constructs the object
	 * @param array $payload An optional hash array of data as the result payload
	 */
	function __construct($payload = false) {
		if ($payload)
			$this->payload = $payload;
	}

	/**
	 * Magic set method to set the data $key to the given $value
	 * @param string $key The key of the data to set
	 * @param mixed $value The value
	 */
	function __set($key, $value) {
		if (property_exists($this, $key)) {
			$this->$key = $value;
			return;
		}

		$this->payload[$key] = $value;
	}

	/**
	 * Magic get method to get the data $value for the given $key
	 * @param string $key The key of the data to get
	 * @return mixed The value
	 */
	function __get($key) {
		if (property_exists($this, $key))
			return $this->$key;

		return $this->payload[$key];
	}

	/**
	 * Magic method to check if the data with the given $key is set
	 * @param string $key The key of the data to check
	 * @param boolean True if the data exists, false otherwise
	 */
	function __isset($key) {
		return isset($this->payload[$key]);
	}
}