<?php

/**
 * ResultKo
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * ResultKo
 *
 * Class that represents a non-successful result from a method when it needs to provide complex results
 *
 * @package Cherrycake
 * @category Classes
 */
class ResultKo extends Result {
	protected $isOk = false;

	/**
	 * Constructs the object
	 * @param array $payload An optional hash array of data as the result payload
	 */
	function __construct($payload = false) {
		parent::__construct($payload);
	}
}