<?php

namespace Cherrycake\Translation;

/**
 * The Language module provides text translations for multilingual sites, working in conjunction with the Locale module.
 *
 * @package Cherrycake
 * @category Modules
 */
class Translation extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		'defaultCacheProviderName' => 'engine', // The default cache provider name to use.
		'defaultCacheTtl' => \Cherrycake\CACHE_TTL_NORMAL, // De default TTL to use.
		'defaultCachePrefix' => 'Translation',
		'defaultBaseLanguage' => \Cherrycake\LANGUAGE_ENGLISH, // The default language on which the texts will be specified in the code when using the Text::build method and the $e->t helper, if no other has been specified.
		'dataFilesDir' => 'translation', // The directory where translations will be stored
		'valueWhenNotTranslated' => false // The value to use when a translation is not available, has to be a string. Set to false to use the base language text instead.
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	var $dependentCoreModules = [
		'Errors',
		'Cache',
		'Locale'
	];

	private function getTranslations() {
	}

	private function isKeyExists($key, $language) {
		return false;
	}

	private function addKey($key, $baseLanguage) {
	}

	private function getTranslation($key, $language) {
		return "a";
	}

	private function buildKey($baseLanguageText, $baseLanguage) {
		return md5($baseLanguageText.$baseLanguage);
	}

	public function translate($text) {
		global $e;

		$baseLanguage = $text->baseLanguage ?? $this->getConfig('defaultBaseLanguage');

		$key = $this->buildKey($text->baseLanguageText, $baseLanguage);

		if (!$this->isKeyExists($key, $e->Locale->getLanguage()))
			$this->addKey($key, $baseLanguage);

		return $this->getTranslation($key, $e->Locale->getLanguage());
	}
}
