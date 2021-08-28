<?php

namespace Cherrycake;

/**
 * The base class for UI components. Intented to be overloaded by specific functionality classes
 *
 * @package Cherrycake
 * @category Modules
 */
class Ui {

	public function __toString() {
		return $this->dump();
	}

	public function set(
		string $key = '',
		string $value = '',
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

	/**
	 * @return string The HTML of the Ui component
	 */
	public function dump() {
		return '';
	}
}
