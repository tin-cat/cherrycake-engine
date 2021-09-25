<?php

namespace Cherrycake\Patterns;

use Cherrycake\Engine;
use Cherrycake\Cache\Cache;

/**
 * * It reads and parses pattern files
 * * Allows pattern nesting and in-pattern commands
 * * Can work in conjunction with Cache module to provide a pattern-level cache
 *
 * Be very careful by not allowing user-entered data or data received via a request to be parsed. Never parse a user-entered information as a pattern.
 */
class Patterns extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"directory" => "patterns", // The directory where patterns are stored
		"defaultCacheProviderName" => "engine", // The default cache provider name to use for cached patterns.
		"defaultCacheTtl" => Cache::TTL_NORMAL, // The default TTL to use for cached patterns.
		"defaultCachePrefix" => "Patterns" // The default prefix to use for cached patterns.
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Output",
		"Errors",
		"Cache"
	];

	/**
	 * @var string $lastEvaluatedCode Contains the last evaluated code
	 */
	private string $lastEvaluatedCode;

	/**
	 * @var string $lastTreatedFile The name of the last treated file
	 */
	private string $lastTreatedFile;

	/**
	 * Parses a pattern and sets the result as the output response payload
	 * @param string $patternName The name of the pattern to out
	 * @param array $setup Additional setup with additional options. See Parse method for details.
	 * @param int $code The response code to send, one of the RESPONSE_* available
	 * @param bool noParse: When set to true, the pattern is returned without any parsing
	 * @param string|array fileToIncludeBeforeParsing: A file (or an array of files) to include whenever parsing this set files, usually for defining variables that can be later used inside the pattern
	 * @param array variables: A hash array of variables passed to be available in-pattern, in the syntax: "variable name" => $variable
	 * @param bool isCache: Whether this pattern should be cached or not, independently of the cachedPatterns Cache config key.
	 * @param string cacheProviderName: A cache provider name that will override the one set in the cachedPatterns or defaultCacheProviderName config key (if any)
	 * @param int cacheTtl: A cache TTL that will override the one set in the cachedPatterns or defaultCacheTtl config key (if any)
	 * @param string cachePrefix: A cache prefix that will override the one set in the cachedPatterns or defaultCachePrefix config key (if any)
	 */
	function out(
		string $patternName,
		?int $code = null,
		string $directoryOverride = '',
		bool $noParse = false,
		string|array $fileToIncludeBeforeParsing = '',
		array $variables = [],
		?bool $isCache = null,
		string $cacheProviderName = '',
		int $cacheTtl = 0,
		string $cachePrefix = ''
	) {
		Engine::e()->Output->setResponse(new \Cherrycake\Actions\ResponseTextHtml(
			code: $code,
			payload: $this->parse(
				patternName: $patternName,
				directoryOverride: $directoryOverride,
				noParse: $noParse,
				fileToIncludeBeforeParsing: $fileToIncludeBeforeParsing,
				variables: $variables,
				isCache: $isCache,
				cacheProviderName: $cacheProviderName,
				cacheTtl: $cacheTtl,
				cachePrefix: $cachePrefix
			),
		));
	}

	/**
	 * Determines whether a given Pattern exists and can be read
	 * @param string $patternName The name of the pattern
	 * @param string $directoryOverride When specified, the pattern is taken from this directory instead of the default configured directory.
	 * @return boolean True if the Pattern exists and is readable, false otherwise
	 */
	function isPatternExists(
		string $patternName,
		string $directoryOverride = '',
	): bool {
		$patternFile = $this->getPatternFileName($patternName, $directoryOverride);
		return file_exists($patternFile) && is_readable($patternFile);
	}

	/**
	 * Parses a pattern
	 * @param string $patternName The name of the pattern to parse
	 * @param string directoryOverride: When specified, the pattern is taken from this directory instead of the default configured directory.
	 * @param bool noParse: When set to true, the pattern is returned without any parsing
	 * @param string|array fileToIncludeBeforeParsing: A file (or an array of files) to include whenever parsing this set files, usually for defining variables that can be later used inside the pattern
	 * @param array variables: A hash array of variables passed to be available in-pattern, in the syntax: "variable name" => $variable
	 * @param ?bool isCache: Whether this pattern should be cached or not, independently of the cachedPatterns Cache config key.
	 * @param string cacheProviderName: A cache provider name that will override the one set in the cachedPatterns or defaultCacheProviderName config key (if any)
	 * @param int cacheTtl: A cache TTL that will override the one set in the cachedPatterns or defaultCacheTtl config key (if any)
	 * @param string cachePrefix: A cache prefix that will override the one set in the cachedPatterns or defaultCachePrefix config key (if any)
	 *
	 * @return string The parsed pattern. Returns false if some error occurred
	 */
	function parse(
		string $patternName,
		string $directoryOverride = '',
		bool $noParse = false,
		string|array $fileToIncludeBeforeParsing = '',
		array $variables = [],
		?bool $isCache = null,
		string $cacheProviderName = '',
		int $cacheTtl = 0,
		string $cachePrefix = ''
	): string {

		$patternFile = $this->getPatternFileName($patternName, $directoryOverride);

		// Check cache
		if (
			(isset($this->getConfig("cachedPatterns")[$patternName]) && is_null($isCache))
			||
			$isCache
		)
			if (
				isset($this->getConfig("cachedPatterns")[$patternName])
				||
				$isCache
			) {
				$cacheProviderName = $cacheProviderName ?: $this->getConfig("cachedPatterns")[$patternName]["cacheProviderName"] ?? false ?: $this->getConfig("defaultCacheProviderName");
				$cacheKey = Cache::buildCacheKey(
					prefix: $setup["cachePrefix"] ?? false ?: $this->getConfig("cachedPatterns")[$patternName]["cachePrefix"] ?? false ?: $this->getConfig("defaultCachePrefix"),
					uniqueId: $patternFile
				);
				if ($buffer = Engine::e()->Cache->$cacheProviderName->get($cacheKey))
					return $buffer;
			}

		if ($noParse)
			return file_get_contents($patternFile);

		if ($fileToIncludeBeforeParsing)
			if (is_array($fileToIncludeBeforeParsing)) {
				foreach ($fileToIncludeBeforeParsing as $fileToIncludeBeforeParsing) {
					include($fileToIncludeBeforeParsing);
				}
			}
			else {
				if ($fileToIncludeBeforeParsing)
					include($fileToIncludeBeforeParsing);
			}

		if ($variables) {
			foreach ($variables as $variableName => $variable)
				eval("\$".$variableName." = \$variable;");
		}

		$this->lastTreatedFile = $patternFile;
		$this->lastEvaluatedCode = file_get_contents($patternFile);
		ob_start();
		eval(
			"namespace ".Engine::e()->getAppNamespace().";".
			"use Cherrycake\Engine;".
			"?> ".
				$this->lastEvaluatedCode.
			"<?php "
		);
		$buffer = ob_get_contents();
		ob_end_clean();

		// Cache store
		if (
			(isset($this->getConfig("cachedPatterns")[$patternName]) && is_null($isCache))
			||
			$isCache
		)
			Engine::e()->Cache->$cacheProviderName->set(
				$cacheKey,
				$buffer,
				$cacheTtl ?: $this->getConfig("cachedPatterns")[$patternName]["cacheTtl"] ?: $this->getConfig("defaultCacheTtl")
			);

		return $buffer;
	}

	/**
	 * Builds the complete filename and path of a pattern
	 * @param string $patternName The pattern name
	 * @param string $directoryOverride When specified, the pattern is taken from this directory instead of the default configured directory.
	 * @return string The complete pattern filename
	 */
	function getPatternFileName(
		string $patternName,
		string $directoryOverride = '',
	): string {
		return ($directoryOverride ? $directoryOverride.($directoryOverride != "" ? "/" : "") : APP_DIR."/".$this->getConfig("directory")."/").$patternName;
	}

	/**
	 * clearCache
	 *
	 * Removes a pattern from cache
	 *
	 * @param string $patternName The pattern name
	 * @param string $directoryOverride When specified, the pattern is taken from this directory instead of the default configured directory.
	 */
	function clearCache($patternName, $directoryOverride = false) {
		if ($cache = $this->getConfig("cache"))
			if ($cachePattern = $cache["items"][$patternName]) {

				$patternFile = $this->getPatternFileName($patternName, $directoryOverride);
				$cacheProviderName = ($cachePattern["cacheProviderName"] ? $cachePattern["cacheProviderName"] : $cache["defaultCacheProviderName"]);
				Engine::e()->Cache->$cache["cacheProviderName"]->delete($patternFile);
			}
	}

	/**
	 * @return string The last evaluated code
	 */
	function getLastEvaluatedCode() {
		return $this->lastEvaluatedCode;
	}

	/**
	 * @return string The name of the last treated file
	 */
	function getLastTreatedFile() {
		return $this->lastTreatedFile;
	}
}
