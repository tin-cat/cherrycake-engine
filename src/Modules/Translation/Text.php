<?php

namespace Cherrycake\Modules\Translation;

use Cherrycake\Classes\Engine;

/**
 * A class that represents a translatable text
 */
class Text {
	public static function build(...$parameters): Text {
		return new Text(...$parameters);
	}

	/**
	 * @param string $key The key that identifies this text uniquely within its category.
	 * @param string $category An optional text category name, to better organize translation files.
	 * @param array $replacements A hash array of the replacements to be done on the translated resulting text.
	 * @return Text A Text object for the given key
	*/
	function __construct(
		public string $key,
		public string $category = '',
		public array $replacements = [],
	) {
		if (stristr($key, '/')) {
			list($category, $key) = explode('/', $key);
			$this->key = $key;
			$this->category = $category;
		}
	}

	function __toString(): string {
		return Engine::e()->Translation->translate($this);
	}

	/**
	 * @param int $language The language
	 * @return string The text translated to the specified language
	 */
	function getForLanguage($language): string {
		return Engine::e()->Translation->translate($this, $language);
	}

	public function getBaseLanguage(): int {
		if ($this->baseLanguage)
			return $this->baseLanguage;
		Engine::e()->loadCoreModule('Translation');
		return Engine::e()->Translation->getConfig('defaultBaseLanguage');
	}

	private function simplifyKey(string $string): string {
		$string = trim(preg_replace('/[\s-]+/', '-', preg_replace('/[^A-Za-z0-9-]+/', '-', preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $string))))), '-');
		$resultingString = '';
		$isNextUppercase = true;
		foreach (str_split($string) as $character) {
			if ($character == '-') {
				$isNextUppercase = true;
				continue;
			}
			if ($isNextUppercase) {
				$resultingString .= strtoupper($character);
				$isNextUppercase = false;
			}
			else
				$resultingString .= $character;
		}
		return $resultingString;
	}

	public function getKey(): string {
		return $this->simplifyKey($this->key);
	}

	public function getCategory(): string|int {
		return $this->category ?: 0;
	}
}
