<?php

namespace Cherrycake\Translation;

/**
 * A class that represents a translatable text
 * @package Cherrycake
 * @category Classes
 */
class Text {
	public $baseLanguageText;
	public $category;
	public $baseLanguage;

	/**
	 * @param string $baseLanguageText The translated text in the base language.
	 * @param string $category An optional text category name, to better organize translation files.
	 * @param int $baseLanguage The language on which the provided $baseLanguageText is. If not specified, the `defaultBaseLanguage` Translation configuration is assumed.
	 * @return Text A Text object for the given key
	*/
	public static function build($baseLanguageText, $category = false, $baseLanguage = false) {
		return new Text($baseLanguageText, $category, $baseLanguage);
	}

	function __construct($baseLanguageText, $category, $baseLanguage = false) {
		$this->baseLanguageText = $baseLanguageText;
		$this->category = $category;
		$this->baseLanguage = $baseLanguage;
	}

	function __toString() {
		global $e;
		$e->loadCoreModule('Translation');
		return $e->Translation->translate($this);
	}

	public function getBaseLanguage() {
		if ($this->baseLanguage)
			return $this->baseLanguage;
		global $e;
		$e->loadCoreModule('Translation');
		return $e->Translation->getConfig('defaultBaseLanguage');
	}

	public function getKey() {
		return md5($this->baseLanguageText.$this->category.$this->getBaseLanguage());
	}

	public function getCategory() {
		return $this->category ?? 0;
	}
}
