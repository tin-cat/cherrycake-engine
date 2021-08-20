<?php

namespace Cherrycake\Language;

/**
 * A class that represents a translation of a text.
 * @package Cherrycake
 * @category Classes
 */
class Translation {
	private $key;

	public static function build($key) {
		return new Translation($key);
	}

	function __construct($key) {
		$this->key = $key;
	}

	function __toString() {
		return $this->key;
	}
}
