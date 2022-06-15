<?php

namespace Cherrycake\Classes;

use Exception;

/**
 * A generic exception class to be thrown whenever security-related exceptions happen
 */
class SecurityException extends Exception {
	/**
	 * @var string $description The exception description, wich provides additional context to the user about the error
	 */
	private ?string $description = null;

	public function __construct(
		string $message,
		int $code = 0,
		?Exception $previous = null,
		string $description = null,
	) {
        $this->description = $description;
        parent::__construct($message, $code, $previous);
    }

	/**
	 * @return string The error description, wich provides additional context to the user about the error
	 */
	function getDescription(): null|string {
		return $this->description;
	}
}
