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
		return $this->parse(Engine::e()->Translation->translate($this));
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

	/**
	 * Parses the passed text, performing replacements and other operations that transform the final string
	 * @param string $text The string to parse
	 */
	private function parse(
		string $text
	): string {

		// Perform replacements
		if ($this->replacements) {
			$text = str_replace(
				array_map(function($item) { return '{'.$item.'}'; }, array_keys($this->replacements)),
				array_values($this->replacements),
				$text
			);
		}

		// `pluralize` command
		// Syntax: {pluralize|<variableName>|<valueWhenSingular>|<valueWhenPlural>}
		if (preg_match_all("/{pluralize\|(.*)\|(.*)\|(.*)}/U", $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				// Check if variableName exists
				if (!isset($this->replacements[$match[1]]))
					continue;
				$text = str_replace(
					$match[0],
					$this->replacements[$match[1]] <= 1 ?
						$match[2]
						:
						$match[3],
					$text
				);
			}
		}

		// `lowercase` command
		// Syntax: {lowercase|<variableName>}
		if (preg_match_all("/{lowercase\|(.*)}/U", $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				// Check if variableName exists
				if (!isset($this->replacements[$match[1]]))
					continue;
				$text = str_replace(
					$match[0],
					strtolower($this->replacements[$match[1]]),
					$text
				);
			}
		}

		// `uppercase` command
		// Syntax: {uppercase|<variableName>}
		if (preg_match_all("/{uppercase\|(.*)}/U", $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				// Check if variableName exists
				if (!isset($this->replacements[$match[1]]))
					continue;
				$text = str_replace(
					$match[0],
					strtoupper($this->replacements[$match[1]]),
					$text
				);
			}
		}

		// `capitalize` command
		// Syntax: {capitalize|<variableName>}
		if (preg_match_all("/{capitalize\|(.*)}/U", $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				// Check if variableName exists
				if (!isset($this->replacements[$match[1]]))
					continue;
				$text = str_replace(
					$match[0],
					ucfirst($this->replacements[$match[1]]),
					$text
				);
			}
		}

		return $text;
	}
}
