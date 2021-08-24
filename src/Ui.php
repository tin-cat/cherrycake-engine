<?php

namespace Cherrycake;

/**
 * The base class for UI components. Intented to be overloaded by specific functionality classes
 *
 * @package Cherrycake
 * @category Modules
 */
class Ui extends BasicObject {

	public function __toString() {
		return $this->dump();
	}

	/**
	 * @return string The HTML of the Ui component
	 */
	public function dump() {
		return '';
	}
}
