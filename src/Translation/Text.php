<?php

namespace Cherrycake\Translation;

/**
 * A class that represents a translatable text
 * @package Cherrycake
 * @category Classes
 */
class Text {
	/**
	 * @param string $baseLanguageText The translated text in the base language.
	 * @param string $category An optional text category name, to better organize translation files.
	 * @param int $baseLanguage The language on which the provided $baseLanguageText is. If not specified, the `defaultBaseLanguage` Translation configuration is assumed.
	 * @return Text A Text object for the given key
	*/
	public static function build(...$parameters): Text {
		return new Text(...$parameters);
	}

	function __construct(
		public string $baseLanguageText,
		public string $category = '',
		public int $baseLanguage = 0,
		public array $replacements = [],
	) {}

	function __toString(): string {
		global $e;
		$e->loadCoreModule('Translation');
		return $e->Translation->translate($this);
	}

	public function getBaseLanguage(): int {
		if ($this->baseLanguage)
			return $this->baseLanguage;
		global $e;
		$e->loadCoreModule('Translation');
		return $e->Translation->getConfig('defaultBaseLanguage');
	}

	private function buildKey(string $string): string {
		$key = '';
		foreach(str_split($string) as $character) {

			if (stristr('¿?!¡.', $character))
				continue;

			foreach ([
				'àáä' => 'a',
				'èéë' => 'e',
				'ìíï' => 'i',
				'òóö' => 'o',
				'ùúü' => 'u'
			] as $search => $replace) {
				if (stristr($search, $character)) {
					$character = $replace;
					break;
				}
			}

			if (stristr('abcdefghijklmnopqrstuvwxyz0123456789', $character))
				$key .= strtolower($character);
			else
				$key .= '_';

		}
		$key = substr(md5($string), 0, 5).'_'.$key;


		// Prevent keys from starting with a number to solve issue with TOML standards
		if (ctype_digit(substr($key, 0, 1)))
			$key = 'x'.substr($key, 1);

		return $key;
	}

	public function getKey(): string {
		return $this->buildKey($this->baseLanguageText);
	}

	public function getCategory(): string|int {
		return $this->category ?: 0;
	}
}
