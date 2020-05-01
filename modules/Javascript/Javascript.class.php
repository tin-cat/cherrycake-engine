<?php

/**
 * Javascript
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * Javascript
 *
 * Module that manages Javascript code.
 *
 * * It works nicely in conjunction with HtmlDocument module.
 * * Javascript code minifying.
 * * Multiple Js files are loaded in just one request.
 * * Treats Js files as patterns in conjunction with Patterns module, allowing the use of calls to the engine within Js code, PHP programming structures, variables, etc.
 * * Implements "file sets"
 * * Implements Javascript code caching in conjunction with Cache module.
 *
 * Configuration example for javascript.config.php:
 * <code>
 * $javascriptConfig = [
 * 	"defaultDirectory" => "res/js", // The default directory where Javascript files in each Javascript set will be searched
 * 	"cacheTtl" => \Cherrycake\CACHE_TTL_LONGEST, // The cache TTL for JS sets
 * 	"cacheProviderName" => "engine", // The cache provider for JS sets
 * 	"lastModifiedTimestamp" => 1, // The last modified timestamp of JS, to handle caches and http cache
 *  "isHttpCache" => false, // Whether to send HTTP Cache headers or not
 *  "httpCacheMaxAge" => false, // The maximum age in seconds for HTTP Cache
 *  "isMinify" => true, // Whether to minify the resulting CSS or not
 * 	"sets" => [ // The Javascript sets available to be included in HTML documents
 * 		"main" => [
 * 			"directory" => "res/javascript/main", // The specific directory where the Javascript files for this set reside
 * 			"files" => [ // The files that this Javascript set contain
 * 				"main.js"
 * 			]
 * 		],
 * 		"uiComponents" => [ // This set must be declared when working with Ui module
 * 			"directory" => "res/javascript/UiComponents",
 * 			"files" => [ // The default Ui-related Javascript files, these are normally the ones that are not bonded to an specific UiComponent, since any other required file is automatically added here by the specific UiComponent object.
 * 			]
 * 		]
 * 	]
 * ];
 * </code>
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
		"cacheProviderName" => "engine", // The cache provider for Javascript sets
		"cachePrefix" => "Javascript",
		"cacheTtl" => \Cherrycake\CACHE_TTL_LONGEST,
		"lastModifiedTimestamp" => false, // The global version
		"isHttpCache" => false, // Whether to send HTTP Cache headers or not
		"httpCacheMaxAge" => \Cherrycake\CACHE_TTL_LONGEST,
		"isMinify" => false
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

		if ($defaultSets = $this->getConfig("defaultSets"))
			foreach ($defaultSets as $setName => $setConfig)
				$this->addSet($setName, $setConfig);

		// Adds cherrycake sets
		$this->addSet(
			"coreUiComponents",
			[
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
	 * Builds a URL to request the given set contents.
	 * Also stores the parsed set in cache for further retrieval by the dump method
	 *
	 * @param mixed $setNames The name of the Javascript set, or an array of them
	 * @return string The Url of the Javascript set
	 */
	function getSetUrl($setNames) {
		global $e;

		if (!$e->Actions->getAction("javascript"))
			return false;

		if (!is_array($setNames))
			$setNames = [$setNames];

		$parameterSetNames = "";
		foreach ($setNames as $setName) {
			$this->storeParsedSetInCache($setName);
			$parameterSetNames .= $setName."-";
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
	 * addFileToSet
	 *
	 * Adds a file to a Javascript set
	 *
	 * @param string $setName The name of the set
	 * @param string $fileName The name of the file
	 */
	function addFileToSet($setName, $fileName) {
		if (!($this->sets[$setName] ?? false) && isset($this->sets[$setName]["files"]) && in_array($fileName, $this->sets[$setName]["files"]))
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
	 * @param string $setName The name of the set
	 * @return string A string that uniquely identifies the current combination of files in the specified set
	 */
	function buildSetUniqId($setName) {
		if (!isset($this->sets[$setName]) || !isset($this->sets[$setName]["files"]))
			return md5($setName);
		return md5($setName.implode($this->sets[$setName]["files"]));
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
			"uniqueId" => $setName
		]);
		if ($e->isDevel() || !$e->Cache->$cacheProviderName->isKey($cacheKey))
			$e->Cache->$cacheProviderName->set(
				$cacheKey,
				$this->parseSet($setName),
				$cacheTtl
			);
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

		if (isset($requestedSet["files"])) {
			$parsed = [];
			foreach ($requestedSet["files"] as $file) {
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

		// Final call to executeDeferredInlineJavascript function that executes all deferred inline javascript when everything else is loaded
		$js .= "if (typeof obj === 'executeDeferredInlineJavascript') executeDeferredInlineJavascript();";

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
		
		$setNames = explode("-", $request->set);

		$cacheProviderName = $this->GetConfig("cacheProviderName");
		
		$js = "";

		foreach($setNames as $setName) {
			$cacheKey = $e->Cache->buildCacheKey([
				"prefix" => "javascriptParsedSet",
				"uniqueId" => $setName
			]);
			if ($e->Cache->$cacheProviderName->isKey($cacheKey))
				$js .= $e->Cache->$cacheProviderName->get($cacheKey);
			else
			if ($e->isDevel())
				$js .= "/* Javascript set \"".$setName."\" not cached */\n";
		}
		
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
}