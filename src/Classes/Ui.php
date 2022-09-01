<?php

namespace Cherrycake\Classes;

/**
 * The base class for UI components. Intented to be overloaded by specific functionality classes
 */
class Ui {

	public function __toString() {
		return $this->dump();
	}

	public function set(
		string $key = '',
		mixed $value = '',
		array $properties = []
	): Ui {
		if ($properties) {
			foreach ($properties as $key => $value)
				$this->$key = $value;
		}
		else
			$this->$key = $value;
		return $this;
	}

	public function get(
		string $key,
	): mixed {
		return $this->$key;
	}

	/**
	 * @return string The HTML of the Ui component
	 */
	public function dump(): string {
		return '';
	}
}
