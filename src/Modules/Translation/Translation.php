<?php

namespace Cherrycake\Modules\Translation;

use Cherrycake\Classes\Engine;
use Cherrycake\Modules\Cache\Cache;
use Cherrycake\Modules\Errors\Errors;
use Cherrycake\Modules\Translation\Text;

/**
 * The Language module provides text translations for multilingual sites, working in conjunction with the Locale module.
 */
class Translation extends \Cherrycake\Classes\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		'cacheProviderName' => 'engine', // The default cache provider name to use.
		'cacheTtl' => Cache::TTL_NORMAL, // The default TTL to use.
		'cacheUniqueId' => 'TranslationData', // The prefix string to add to cached keys
		'dataFilesDir' => APP_DIR.'/translation', // The directory where translations will be stored
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		'Errors',
		'Cache',
		'Locale'
	];

	private $translations;
	private $textsToStore;
	private $isCreateFilesOnEnd = false;

	public function init(): bool {
		$this->loadTranslations();
		return true;
	}

	/**
	 * Loads all the available translation data files matching the configured Locale available languages
	 */
	private function loadTranslations() {

		if (!Engine::e()->isDevel()) {
			$cacheProviderName = $this->GetConfig('cacheProviderName');
			$cacheTtl = $this->GetConfig('cacheTtl');
			$cacheKey = Engine::e()->Cache->buildCacheKey(
				uniqueId: $this->GetConfig('cacheUniqueId')
			);

			if (Engine::e()->Cache->$cacheProviderName->isKey($cacheKey)) {
				$this->translations = Engine::e()->Cache->$cacheProviderName->get($cacheKey);
				return;
			}
		}

		foreach (Engine::e()->Locale->getAvailaleLanguages() as $language)
			$this->loadTranslationFile($language);

		if (!Engine::e()->isDevel())
			Engine::e()->Cache->$cacheProviderName->set($cacheKey, $this->translations, $cacheTtl);
	}

	/**
	 * Loads the translation file for the given language
	 * @param int $language The language to load
	 * @return boolean True if loading went ok, false otherwise. If the file doesn't exists, it's not considered as an error, and it still returns true;
	 */
	private function loadTranslationFile($language) {

		$filePath = $this->getTranslationFilePath($language);

		if (!file_exists($filePath))
			return [];

		if (!is_readable($filePath)) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: 'Translation file is not readable',
				variables: [
					'file' => $filePath
				]
			);
			return false;
		}

		if (!$fileContents = file_get_contents($filePath)) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: 'Couldn\'t read translation file',
				variables: [
					'file' => $filePath
				]
			);
			return false;
		}

		try {
			$translations = \Yosymfony\Toml\Toml::Parse($fileContents);
		}
		catch(\Yosymfony\ParserUtils\SyntaxErrorException $e) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: 'Couln\'t parse translation TOML file',
				variables: [
					'file' => $filePath,
					'error' => $e->getMessage()
				]
			);
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
		return strtolower(str_replace(' ', '_', Engine::e()->Locale->getLanguageName($language, ['forceLanguage' => \Cherrycake\Modules\Locale\Locale::ENGLISH]))).'.toml';
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
	private function storeText(Text $text) {
		$this->textsToStore[] = $text;
		foreach (Engine::e()->Locale->getAvailaleLanguages() as $language) {
			$this->translations[$text->getCategory()][$text->getKey()][$language] = $text->getKey();
		}
		$this->isCreateFilesOnEnd = true;
	}

	/**
	 * Creates the data files for all available languages using the currently loaded translations
	 */
	private function createFiles() {
		foreach (Engine::e()->Locale->getAvailaleLanguages() as $language)
			$this->createFile($language);
	}

	/**
	 * Creates the data file for the given $language using the currently loaded translations
	 * @param int $language
	 * @return bool True if creation went ok, false otherwise
	 */
	private function createFile($language) {

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
				$data[$text->getCategory()][$text->getKey()] = $text->getKey();
			}
		}

		// Create the TOML string
		$toml = $this->buildToml($data, $language);

		if (!$fp = fopen($fileName, 'w')) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: 'Couldn\'t open translation data file for writing',
				variables: [
					'fileName' => $fileName
				]
			);
			return false;
		}

		if (!fwrite($fp, $toml)) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: 'Couldn\'t write to translation data file',
				variables: [
					'fileName' => $fileName
				]
			);
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
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: 'Couldn\'t create translations data files directory',
				variables: [
					'directory' => $dir
				]
			);
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

	private function isTextStored(Text $text) {
		return isset($this->translations[$text->getCategory()][$text->getKey()]);
	}

	/**
	 * @param string $text The text object
	 * @param int $language The language to translate the text to. The current language will be used if not specified.
	 * @return string The translated text
	 */
	private function getTranslation(
		Text $text,
		?int $language = null
	): string {
		if (is_null($language))
			$language = Engine::e()->Locale->getLanguage();
		$translation = $this->translations[$text->getCategory()][$text->getKey()][$language] ?? '';
		return $this->getFromArray($this->translations[$text->getCategory()][$text->getKey()], $language);
	}

	/**
	 * @param $data All the available translations
	 * @param int $language The language to get the translation in
	 * @return mixed The data in the specified language. Null if there wasn't any data for the specified language.
	 */
	public function getFromArray(
		array $data,
		?int $language = null
	): mixed {
		return $data[is_null($language) ? Engine::e()->Locale->getLanguage(): $language] ?? null;
	}

	/**
	 * @param string $text The text object
	 * @param int $language The language to translate the text to. The current language will be used if not specified.
	 * @return string The translated text
	 */
	public function translate(
		Text $text,
		?int $language = null
	): string {
		if (!$this->isTextStored($text))
			$this->storeText($text);

		return $this->getTranslation($text, $language);
	}

	private function buildToml($data, $language) {

		$toml = new \Yosymfony\Toml\TomlBuilder;

		$toml = $toml
			->addComment('Translations for '.Engine::e()->Locale->getLanguageName($language).' ('.Engine::e()->Locale->getLanguageCode($language).')')
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
