<?php

namespace Cherrycake\Classes;

use Exception;

/**
 * A generic exception class that provides some additional enriched information
 */
class RichException extends Exception {
	/**
	 * @var string $description The exception description, wich provides additional context to the user about the error
	 */
	private ?string $description = null;

	/**
	 * @var string $develDescription The exception description for developers, wich provides additional context to the developer about the error
	 */
	private ?string $develDescription = null;

	/**
	 * @param $description An extended description of the exception, additional to the Exception's message
	 * @param $develDescription An extended description of the exception intended only for developers
	 */
	public function __construct(
		string $message,
		int $code = 0,
		?Exception $previous = null,
		string $description = null,
		string $develDescription = null,
	) {
		$this->description = $description;
		$this->develDescription = $develDescription;
        parent::__construct($message, $code, $previous);
    }

	/**
	 * @return string The error description, wich provides additional context to the user about the error
	 */
	function getDescription(): null|string {
		return
			($this->develDescription && Engine::e()->isDevel() ? ' ('.$this->develDescription.')' : null);
	}

	/**
	 * @return string The error description for developers, wich provides additional context to the developer about the error
	 */
	function getDevelDescription(): null|string {
		return $this->develDescription;
	}

	function __toString(): string {
		return
			$this->message.
			($this->description ?
				' ('.$this->description.')'
			: null).
			($this->develDescription ?
				' ('.$this->develDescription.')'
			: null);
	}
}
