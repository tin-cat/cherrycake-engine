<?php

namespace Cherrycake\Translation;

/**
 * A class that represents a translatable text
 * @package Cherrycake
 * @category Classes
 */
class Text {
	public $baseLanguageText;
	public $baseLanguage;

	/**
	 * @param string $baseLanguageText The text translated to the base language
	 * @return Text A Text object for the given key
	*/
	public static function build($baseLanguageText, $baseLanguage = false) {
		return new Text($baseLanguageText, $baseLanguage);
	}

	function __construct($baseLanguageText, $baseLanguage = false) {
		$this->baseLanguageText = $baseLanguageText;
		$this->baseLanguage = $baseLanguage;
	}

	function __toString() {
		global $e;
		$e->loadCoreModule('Translation');
		return $e->Translation->translate($this);
	}
}
