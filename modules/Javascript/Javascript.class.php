<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Module that manages Javascript code.
 *
 * @package Cherrycake
 * @category Modules
 */
class Javascript  extends \Cherrycake\Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"defaultSetOrder" => 100, // The default order to assign to sets when no order is specified
		"cacheProviderName" => "engine", // The name of the cache provider to use
		"cacheTtl" => \Cherrycake\CACHE_TTL_LONGEST,
		"lastModifiedTimestamp" => false, // The timestamp of the last modification to the JavaScript files, or any other string that will serve as a unique identifier to force browser cache reloading when needed
		"isHttpCache" => false, // Whether to send HTTP Cache headers or not
		"httpCacheMaxAge" => \Cherrycake\CACHE_TTL_LONGEST, //  The TTL of the HTTP Cache
		"isMinify" => false // Whether to minify the JavaScript code or not
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	var $dependentCoreModules = [
		"Errors",
		"Actions",
		"Cache",
		"Patterns",
		"Locale"
	];

	/**
	 * @var array $sets Contains an array of sets of Javascript files
	 */
	private $sets;

	/**
	 * init
	 *
	 * Initializes the module
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		if (!parent::init())
			return false;

		if ($sets = $this->getConfig("sets"))
			foreach ($sets as $setName => $setConfig)
				$this->addSet($setName, $setConfig);

		// Adds cherrycake sets
		$this->addSet(
			"coreUiComponents",
			[
				"order" => 10,
				"directory" => ENGINE_DIR."/res/javascript/uicomponents"
			]
		);

		return true;
	}

	/**
	 * mapActions
	 *
	 * Maps the Actions to which this module must respond
	 */
	public static function mapActions() {
		global $e;

		$e->Actions->mapAction(
			"javascript",
			new \Cherrycake\ActionJavascript([
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CORE,
				"moduleName" => "Javascript",
				"methodName" => "dump",
				"request" => new \Cherrycake\Request([
					"pathComponents" => [
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "js"
						])
					],
					"parameters" => [
						new \Cherrycake\RequestParameter([
							"name" => "set",
							"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET
						]),
						new \Cherrycake\RequestParameter([
							"name" => "version",
							"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET
						])
					]
				])
			])
		);
	}

	/**
	 * addSet
	 *
	 * @param $setName
	 * @param $setConfig
	 */
	function addSet($setName, $setConfig) {
		$this->sets[$setName] = $setConfig;
	}
	
	/**
	 * Builds a unique id that uniquely identifies the specified set with its current files and its contents.
	 * Unique ids for sets change if the list of files in a set changes, or if the contents of any of the files changes.
	 * Set unique ids are stored in shared memory, and generated when they're not found there.
	 * Set unique ids are stored with a TTL of 1 if the app is in development mode.
	 * This ultimately causes the browser to easily cache the requests because the URL uniquely identifies versions, automatically causing a cache miss when the contents have changed, avoiding any need to keep track of cache versions manually.
	 * @param string $setName The name of the set
	 * @return string A uniq id
	 */
	function getSetUniqueId($setName) {
		global $e;

		$cacheProviderName = $this->GetConfig("cacheProviderName");
		$cacheTtl = $e->isDevel() ? 1 : $this->GetConfig("cacheTtl");
		$cacheKey = $e->Cache->buildCacheKey([
			"prefix" => "javascriptSetUniqueId",
			"uniqueId" => $setName
		]);

		if ($e->Cache->$cacheProviderName->isKey($cacheKey))
			return $e->Cache->$cacheProviderName->get($cacheKey);
		
		$uniqId = md5($this->parseSet($setName));

		$e->Cache->$cacheProviderName->set($cacheKey, $uniqId, $cacheTtl);
		return $uniqId;
	}

	/**
	 * Builds a URL to request the given set contents.
	 * Also stores the parsed set in cache for further retrieval by the dump method
	 *
	 * @param mixed $setNames Optional nhe name of the Javascript set, or an array of them. If set to false, all available sets are used.
	 * @return string The Url of the Javascript set
	 */
	function getSetUrl($setNames = false) {
		global $e;

		$orderedSets = $this->getOrderedSets($setNames);
		$parameterSetNames = "";
		foreach ($orderedSets as $setName => $set) {
			$parameterSetNames .= $setName.":".$this->getSetUniqueId($setName)."-";
			$this->storeParsedSetInCache($setName);
		}
		$parameterSetNames = substr($parameterSetNames, 0, -1);
		
		return $e->Actions->getAction("javascript")->request->buildUrl([
			"parameterValues" => [
				"set" => $parameterSetNames,
				"version" => $this->getConfig("lastModifiedTimestamp")
			]
		]);
	}

	/**
	 * Returns an ordered version of the current sets
	 * @param mixed $setNames Optional name of the Css set, or an array of them. If set to false, all available sets are used.
	 * @return array The sets
	 */
	function getOrderedSets($setNames = false) {
		if ($setNames == false)
			$setNames = array_keys($this->sets);

		if (!is_array($setNames))
			$setNames = [$setNames];
		
		foreach ($setNames as $setName)
			$orderedSetNames[$this->sets[$setName]["order"] ?? $this->getConfig("defaultSetOrder")][] = $setName;
		ksort($orderedSetNames);

		foreach ($orderedSetNames as $order => $setNames) {
			foreach ($setNames as $setName) {
				$orderedSets[$setName] = $this->sets[$setName];
			}
		}

		return $orderedSets;
	}

	/**
	 * addFileToSet
	 *
	 * Adds a file to a Javascript set
	 *
	 * @param string $setName The name of the set
	 * @param string $fileName The name of the file
	 */
	function addFileToSet($setName, $fileName) {
		if (
			isset($this->sets[$setName])
			&&
			isset($this->sets[$setName]["files"])
			&&
			in_array($fileName, $this->sets[$setName]["files"])
		)
			return;
		$this->sets[$setName]["files"][] = $fileName;
	}

	/**
	 * addJavascriptToSet
	 *
	 * Adds the specified Javascript to a set
	 *
	 * @param string $setName The name of the set
	 * @param string $javascript The Javascript
	 */
	function addJavascriptToSet($setName, $javascript) {
		$this->sets[$setName]["appendJavascript"] = ($this->sets[$setName]["appendJavascript"] ?? false ?: "").$javascript;
	}

	/**
	 * Parses the given set and stores it into cache.
	 * @param string $setName The name of the set
	 */
	function storeParsedSetInCache($setName) {
		global $e;
		// Get the unique id for each set with its currently added files and see if it's in cache. If it's not, add it to cache.
		$cacheProviderName = $this->GetConfig("cacheProviderName");
		$cacheTtl = $this->GetConfig("cacheTtl");
		$cacheKey = $e->Cache->buildCacheKey([
			"prefix" => "javascriptParsedSet",
			"setName" => $setName,
			"uniqueId" => $this->getSetUniqueId($setName)
		]);
		if (!$e->Cache->$cacheProviderName->isKey($cacheKey))
			$e->Cache->$cacheProviderName->set(
				$cacheKey,
				$this->parseSet($setName),
				$cacheTtl
			);
	}

	/*
	* Builds a list of the files on the specified set.
	* @param string $setName The name of the set
	* @return array The names of the files on the set, or false if no files
	*/
	function getSetFiles($setName) {
		global $e;

		$requestedSet = $this->sets[$setName];

		if ($requestedSet["isIncludeAllFilesInDirectory"] ?? false) {
			if ($e->isDevel() && !is_dir($requestedSet["directory"])) {
				$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "Couldn't open JavaScript directory",
					"errorVariables" => [
						"setName" => $setName,
						"directory" => $requestedSet["directory"]
					]
				]);
			}
			if ($handler = opendir($requestedSet["directory"])) {
				while (false !== ($entry = readdir($handler))) {
					if (substr($entry, -3) == ".js")
						$requestedSet["files"][] = $entry;
				}
				closedir($handler);
			}
		}

		return $requestedSet["files"] ?? false;
	}

	/**
	 * Parses the given set
	 * @param string $setName The name of the set
	 * @return string The parsed set
	 */
	function parseSet($setName) {
		global $e;

		if (!isset($this->sets[$setName]))
			return null;
		
		if ($e->isDevel())
			$develInformation = "\nSet \"".$setName."\":\n";
		
		$requestedSet = $this->sets[$setName];

		$js = "";

		$files = $this->getSetFiles($setName);

		if ($files) {
			$parsed = [];
			foreach ($files as $file) {
				if (in_array($file, $parsed))
					continue;
				else
					$parsed[] = $file;
				
				if ($e->isDevel())
					$develInformation .= $requestedSet["directory"]."/".$file."\n";
				
				$js .= $e->Patterns->parse(
					$file,
					[
						"directoryOverride" => $requestedSet["directory"] ?? false,
						"fileToIncludeBeforeParsing" => $requestedSet["variablesFile"] ?? false
					]
				)."\n";
			}
		}

		if (isset($requestedSet["appendJavascript"]))
			$js .=
				($e->isDevel() ? "\n/* ".$setName." appended JavaScript */\n\n" : null).
				$requestedSet["appendJavascript"];

		// Include variablesFile specified files
		if (isset($requestedSet["variablesFile"]))
			if (is_array($requestedSet["variablesFile"]))
				foreach ($requestedSet["variablesFile"] as $fileName)
					include($fileName);
			else
				include($requestedSet["variablesFile"]);

		if($this->getConfig("isMinify"))
			$js = $this->minify($js);
		
		if ($e->isDevel())
			$js = "/*\n".$develInformation."\n*/\n\n".$js;

		return $js;
	}


	/**
	 * dump
	 *
	 * Outputs the requested Javascript sets to the client.
	 * It guesses what Javascript sets to dump via the "set" get parameter.
	 * It handles Javascript caching,
	 * Intended to be called from a <script src ...>
	 *
	 * @param Request $request The Request object received
	 */
	function dump($request) {
		global $e;

		if ($this->getConfig("isHttpCache"))
			\Cherrycake\HttpCache::init($this->getConfig("lastModifiedTimestamp"), $this->getConfig("httpCacheMaxAge"));

		if (!$request->set) {
			$e->Output->setResponse(new \Cherrycake\ResponseTextCss());
			return;
		}
		
		$setPairs = explode("-", $request->set);

		$cacheProviderName = $this->GetConfig("cacheProviderName");
		
		$js = "";

		foreach($setPairs as $setPair) {
			list($setName, $setUniqueId) = explode(":", $setPair);
			$cacheKey = $e->Cache->buildCacheKey([
				"prefix" => "javascriptParsedSet",
				"setName" => $setName,
				"uniqueId" => $setUniqueId
			]);
			if ($e->Cache->$cacheProviderName->isKey($cacheKey))
				$js .= $e->Cache->$cacheProviderName->get($cacheKey);
			else
			if ($e->isDevel())
				$js .= "/* Javascript set \"".$setName."\" not cached */\n";
		}

		// Final call to executeDeferredInlineJavascript function that executes all deferred inline javascript when everything else is loaded
		$js .= "if (typeof obj === 'executeDeferredInlineJavascript') executeDeferredInlineJavascript();";
		
		$e->Output->setResponse(new \Cherrycake\ResponseApplicationJavascript([
			"payload" => $js
		]));
		return;
	}

	/**
	 * minify
	 *
	 * Minifies Javascript code
	 *
	 * @param string $javascript The Javascript to minify
	 * @return string The minified Javascript
	 */
	function minify($javascript) {
		return \Cherrycake\JavascriptMinifier::minify($javascript);
	}

	/**
	 * safeString
	 *
	 * Returns an escaped version of the given $string that can be safely used between javascript single-quotes
	 *
	 * @param string $string The string to treat
	 * @return string The escaped string
	 */
	function safeString($string) {
		return str_replace("'", "\\'", $string);
	}

	/**
	 * @return array Status information
	 */
	function getStatus() {
		if (is_array($this->sets)) {
			$orderedSets = $this->getOrderedSets();
			foreach ($orderedSets as $setName => $set) {

				$r[$setName]["order"] = $set["order"] ?? $this->getConfig("defaultSetOrder");

				$r[$setName]["directory"] = $set["directory"];

				if (isset($set["variablesFile"]))
					$r[$setName]["variablesFile"] = implode(", ", $set["variablesFile"]);
				
				if ($set["isIncludeAllFilesInDirectory"] ?? false)
				$r[$setName]["files"][] = $set["directory"]."/*.js";

				if (!isset($set["files"]))
					continue;

				foreach ($set["files"] as $file)
					$r[$setName]["files"][] = $file;

			}
			reset($this->sets);
		}

		return $r ?? null;
	}
}