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
		'cacheProviderName' => 'engine', // The default cache provider name to use.
		'cacheTtl' => \Cherrycake\CACHE_TTL_NORMAL, // The default TTL to use.
		'cacheUniqueId' => 'TranslationData', // The prefix string to add to cached keys
		'defaultBaseLanguage' => \Cherrycake\LANGUAGE_ENGLISH, // The default language on which the texts will be specified in the code when using the Text::build method and the $e->t helper, if no other has been specified.
		'dataFilesDir' => APP_DIR.'/translation', // The directory where translations will be stored
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

	private $translations;
	private $textsToStore;
	private $isCreateFilesOnEnd = false;

	public function init() {
		$this->loadTranslations();
		return true;
	}

	/**
	 * Loads all the available translation data files matching the configured Locale available languages
	 */
	private function loadTranslations() {
		global $e;

		if (!$e->isDevel()) {
			$cacheProviderName = $this->GetConfig('cacheProviderName');
			$cacheTtl = $this->GetConfig('cacheTtl');
			$cacheKey = $e->Cache->buildCacheKey([
				'uniqueId' => $this->GetConfig('cacheUniqueId')
			]);

			if ($e->Cache->$cacheProviderName->isKey($cacheKey)) {
				$this->translations = $e->Cache->$cacheProviderName->get($cacheKey);
				return;
			}
		}

		foreach ($e->Locale->getAvailaleLanguages() as $language)
			$this->loadTranslationFile($language);

		if (!$e->isDevel())
			$e->Cache->$cacheProviderName->set($cacheKey, $this->translations, $cacheTtl);
	}

	/**
	 * Loads the translation file for the given language
	 * @param int $language The language to load
	 * @return boolean True if loading went ok, false otherwise. If the file doesn't exists, it's not considered as an error, and it still returns true;
	 */
	private function loadTranslationFile($language) {
		global $e;

		$filePath = $this->getTranslationFilePath($language);

		if (!file_exists($filePath))
			return [];

		if (!is_readable($filePath)) {
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				'errorDescription' => 'Translation file is not readable',
				'errorVariables' => [
					'file' => $filePath
				]
			]);
			return false;
		}

		if (!$fileContents = file_get_contents($filePath)) {
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				'errorDescription' => 'Couldn\'t read translation file',
				'errorVariables' => [
					'file' => $filePath
				]
			]);
			return false;
		}

		try {
			$translations = \Yosymfony\Toml\Toml::Parse($fileContents);
		}
		catch(\Yosymfony\ParserUtils\SyntaxErrorException $e) {
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				'errorDescription' => 'Couln\'t parse translation TOML file',
				'errorVariables' => [
					'file' => $filePath,
					'error' => $e->getMessage()
				]
			]);
			return false;
		}

		foreach ($translations as $categoryOrKey => $value) {
			if (is_array($value)) {
				foreach ($value as $key => $value)
					$this->translations[$categoryOrKey][$key][$language] = $value;
			}
			else
				$this->translations[0][$categoryOrKey][$language] = $value;
		}
	}

	/**
	 * Gets the file name of the translation data file for the given $language
	 * @param int $language The language for which to get the translation file name for
	 * @return string The file name
	 */
	private function getTranslationFileName($language) {
		global $e;
		return strtolower(str_replace(' ', '_', $e->Locale->getLanguageName($language))).'.toml';
	}

	/**
	 * Gets the complete file path, including the file name, of the translation data file for the given $language
	 * @param int $language The language for which to get the translation file path for
	 * @return string The path
	 */
	private function getTranslationFilePath($language) {
		return $this->getConfig('dataFilesDir').'/'.$this->getTranslationFileName($language);
	}

	/**
	 * Adds the provided $text to the currently loaded translations and sets the flag to recreate translation files on module's end
	 */
	private function storeText($text) {
		global $e;
		$this->textsToStore[] = $text;
		foreach ($e->Locale->getAvailaleLanguages() as $language)
			$this->translations[$text->getCategory()][$text->getKey()][$language] = $text->baseLanguageText;
		$this->isCreateFilesOnEnd = true;
	}

	/**
	 * Creates the data files for all available languages using the currently loaded translations
	 */
	private function createFiles() {
		global $e;
		foreach ($e->Locale->getAvailaleLanguages() as $language)
			$this->createFile($language);
	}

	/**
	 * Creates the data file for the given $language using the currently loaded translations
	 * @param int $language
	 * @return bool True if creation went ok, false otherwise
	 */
	private function createFile($language) {
		global $e;

		if (!$this->createDataFilesDir())
			return;

		$fileName = $this->getTranslationFilePath($language);

		// Create the data array for the language
		$data = [];

		// Create data for the already loaded texts, if any
		if (is_array($this->translations)) {
			foreach ($this->translations as $category => $categoryTranslations) {
				foreach ($categoryTranslations as $key => $translation)
					$data[$category][$key] = $translation[$language] ?? '';
			}
		}

		// Create the data for the newly found texts on textsToStore, if any
		if (is_array($this->textsToStore)) {
			foreach ($this->textsToStore as $text) {
				$data[$text->getCategory()][$text->getKey()] = $text->baseLanguageText;
			}
		}

		// Create the TOML string
		$toml = $this->buildToml($data, $language);

		if (!$fp = fopen($fileName, 'w')) {
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				'errorDescription' => 'Couldn\'t open translation data file for writing',
				'errorVariables' => [
					'fileName' => $fileName
				]
			]);
			return false;
		}

		if (!fwrite($fp, $toml)) {
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				'errorDescription' => 'Couldn\'t write to translation data file',
				'errorVariables' => [
					'fileName' => $fileName
				]
			]);
			return false;
		}

		fclose($fp);

		return true;
	}

	private function createDataFilesDir() {
		$dir = $this->getConfig('dataFilesDir');
		if (file_exists($dir) && is_dir($dir))
			return true;

		if (!mkdir($dir, 0777, true)) {
			global $e;
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				'errorDescription' => 'Couldn\'t create translations data files directory',
				'errorVariables' => [
					'directory' => $dir
				]
			]);
			return false;
		}

		return true;
	}

	/**
	 * If there have been some translation keys that were not found on the loaded translations, add them now
	 */
	public function end() {
		if ($this->isCreateFilesOnEnd)
			$this->createFiles();
	}

	private function isTextStored($text) {
		return isset($this->translations[$text->getCategory()][$text->getKey()]);
	}

	private function getTranslation($text) {
		global $e;
		return $this->translations[$text->getCategory()][$text->getKey()][$e->Locale->getLanguage()] ?? '';
	}

	public function translate($text) {
		if (!$this->isTextStored($text))
			$this->storeText($text);

		return $this->getTranslation($text);
	}

	private function buildToml($data, $language) {
		global $e;

		$toml = new \Yosymfony\Toml\TomlBuilder;

		$toml = $toml
			->addComment('Translations for '.$e->Locale->getLanguageName($language).' ('.$e->Locale->getLanguageCode($language).')')
			->addComment('TOML specification: https://github.com/toml-lang/toml/blob/master/toml.md');

		$lastCategory = null;
		foreach ($data as $category => $items) {

			if ($category !== $lastCategory) {
				if ($category !== 0)
					$toml->addTable($category);
				$lastCategory = $category;
			}

			foreach ($items as $key => $translation)
				$toml->addValue($key, $translation);
		}

		return $toml->getTomlString();
	}
}
