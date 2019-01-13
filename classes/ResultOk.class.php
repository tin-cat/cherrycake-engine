<?php

/**
 * ResultOk
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * ResultOk
 *
 * Class that represents a successful result from a method when it needs to provide complex results
 *
 * @package Cherrycake
 * @category Classes
 */
class ResultOk extends Result {
	protected $isOk = true;

	/**
	 * Constructs the object
	 * @param array $payload An optional hash array of data as the result payload
	 */
	function __construct($payload = false) {
		parent::__construct($payload);
	}
}