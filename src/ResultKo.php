<?php

namespace Cherrycake;

/**
 * Class that represents a non-successful result from a method when it needs to provide complex results
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
