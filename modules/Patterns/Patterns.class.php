<?php

/**
 * Patterns
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * Patterns
 *
 * Module to manage patterns.
 *
 * * It reads and parses pattern files
 * * Allows pattern nesting and in-pattern commands
 * * Can work in conjunction with Cache module to provide a pattern-level cache
 *
 * Be very careful by not allowing user-entered data or data received via a request to be parsed. Never parse a user-entered information as a pattern.
 * It takes configuration from the App-layer configuration file.
 *
 * Configuration example for patterns.config.php:
 * <code>
 * $patternsConfig = [
 * 	"directory" => "patterns", // The directory where patterns reside
 * 	"cache" => [
 * 		"cacheProviderName" => "fast", // The default cache provider to use for cached patterns when no specific per-pattern cache provider is specified
 * 		"items" => [
 * 			"home/cacheddemo.html" => [  // A pattern to cache
 * 				"ttl" => \Cherrycake\Modules\CACHE_TTL_MINIMAL, // The TTL
 *				"cacheProviderName" => "huge" // A cache provider to use for this pattern that overrides the default one specified above
 * 			]
 * 		]
 * 	]
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Patterns extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"cachePrefix" => "Patterns"
	];

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Output",
		"Errors",
		"Cache"
	];

	/**
	 * @var string $lastEvaluatedCode Contains the last evaluated code
	 */
	private $lastEvaluatedCode;

	/**
	 * @var string $lastTreatedFile The name of the last treated file
	 */
	private $lastTreatedFile;

	/**
	 * init
	 *
	 * Initializes the module
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		return true;
	}

	/**
	 * out
	 *
	 * Parses a pattern and sets the result as the output response payload
	 *
	 * @param string $patternName The name of the pattern to out
	 * @param array $setup Additional setup with additional options. See Parse method for details.
	 * @param integet $code The response code to send, one of the RESPONSE_* available
	 */
	function out($patternName, $setup = false, $code = false) {
		global $e;
		$e->Output->setResponse(new \Cherrycake\ResponseTextHtml([
			"code" => $code,
			"payload" => $this->parse($patternName, $setup)
		]));
	}

	/**
	 * Determines whether a given Pattern exists and can be read
	 * 
	 * @param string $patternName The name of the pattern
	 * @param array $setup Additional setup with the following possible keys:
	 * directoryOverride: When specified, the pattern is taken from this directory instead of the default configured directory.
	 * @return boolean True if the Pattern exists and is readable, false otherwise
	 */
	function isPatternExists($patternName, $setup = false) {
		$patternFile = $this->getPatternFileName($patternName, $setup["directoryOverride"] ?? false);
		return file_exists($patternFile) && is_readable($patternFile);
	}

	/**
	 * parse
	 *
	 * Parses a pattern
	 *
	 * @param string $patternName The name of the pattern to parse
	 * @param array $setup Additional setup with the following possible keys:
	 * * directoryOverride: When specified, the pattern is taken from this directory instead of the default configured directory.
	 * * noParse: When set to true, the pattern is returned without any parsing
	 * * fileToIncludeBeforeParsing: A file (or an array of files) to include whenever parsing this set files, usually for defining variables that can be later used inside the pattern
	 * * variables: A hash array of variables passed to be available in-pattern, in the syntax: "variable name" => $variable
	 *
	 * @return string The parsed pattern. Returns false if some error occurred
	 */
	function parse($patternName, $setup = false) {
		global $e;

		$patternFile = $this->getPatternFileName($patternName, isset($setup["directoryOverride"]) ? $setup["directoryOverride"] : null);

		// Check cache
		if (
			$cache = $this->getConfig("cache")
			&&
			isset($cache["items"])
		)
			if ($cachePattern = $cache["items"][$patternName]) {
				$cacheProviderName = ($cachePattern["cacheProviderName"] ? $cachePattern["cacheProviderName"] : $cache["defaultCacheProviderName"]);
				$cacheKey = \Cherrycake\Modules\Cache::buildCacheKey([
					"prefix" => $this->getConfig("cachePrefix"),
					"uniqueId" => $patternFile
				]);
				if ($buffer = $e->Cache->$cacheProviderName->get($cacheKey))
					return $buffer;
			}

		if (isset($setup["noParse"]))
			return file_get_contents($patternFile);

		if (isset($setup["fileToIncludeBeforeParsing"]))
			if (is_array($setup["fileToIncludeBeforeParsing"]))
				foreach ($setup["fileToIncludeBeforeParsing"] as $fileToIncludeBeforeParsing) {
					include($fileToIncludeBeforeParsing);
				}
				else {
					if ($setup["fileToIncludeBeforeParsing"] ?? false)
						include($setup["fileToIncludeBeforeParsing"]);
				}

		if (isset($setup["variables"])) {
			foreach ($setup["variables"] as $variableName => $variable)
				eval("\$".$variableName." = \$variable;");
		}

		$this->lastTreatedFile = $patternFile;
		$this->lastEvaluatedCode = file_get_contents($patternFile);
		ob_start();
		eval("?>".$this->lastEvaluatedCode."<?php");
		$buffer = ob_get_contents();
		ob_end_clean();

		// Cache store
		if (isset($cachePattern))
			$e->Cache->$cacheProviderName->set($cacheKey, $buffer, $cachePattern["ttl"]);

		return $buffer;
	}

	/**
	 * getPatternFileName
	 *
	 * Builds the complete filename and path of a pattern
	 *
	 * @param string $patternName The pattern name
	 * @param string $directoryOverride When specified, the pattern is taken from this directory instead of the default configured directory.
	 * @return string The complete pattern filename
	 */
	function getPatternFileName($patternName, $directoryOverride = null) {
		return (!is_null($directoryOverride) ? $directoryOverride.($directoryOverride != "" ? "/" : "") : APP_DIR."/".$this->getConfig("directory")."/").$patternName;
	}

	/**
	 * wipeCache
	 *
	 * Deletes a cached pattern from the cache
	 *
	 * @param string $patternName The pattern name
	 * @param string $directoryOverride When specified, the pattern is taken from this directory instead of the default configured directory.
	 */
	function wipeCache($patternName, $directoryOverride = false) {
		if ($cache = $this->getConfig("cache"))
			if ($cachePattern = $cache["items"][$patternName]) {
				global $e;

				$patternFile = $this->getPatternFileName($patternName, $directoryOverride);
				$cacheProviderName = ($cachePattern["cacheProviderName"] ? $cachePattern["cacheProviderName"] : $cache["defaultCacheProviderName"]);
				$e->Cache->$cache["cacheProviderName"]->delete($patternFile);
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