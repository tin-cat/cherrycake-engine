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
 * 	"cacheTtl" => \Cherrycake\Modules\CACHE_TTL_LONGEST, // The cache TTL for JS sets
 * 	"cacheProviderName" => "fast", // The cache provider for JS sets
 * 	"lastModifiedTimestamp" => 1, // The last modified timestamp of JS, to handle caches and http cache
 *  "isCache" => false, // Whether to use cache or not
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
 * 		"UiComponents" => [ // This set must be declared when working with Ui module
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
class Javascript extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"cachePrefix" => "Javascript",
		"cacheTtl" => \Cherrycake\Modules\CACHE_TTL_NORMAL,
		"lastModifiedTimestamp" => 1,
		"isCache" => false,
		"isHttpCache" => false,
		"httpCacheMaxAge" => \Cherrycake\Modules\CACHE_TTL_LONGEST,
		"isMinify" => true
	];

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Errors",
		"Actions",
		"Cache",
		"Patterns",
		"Ui"
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
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		if ($defaultSets = $this->getConfig("defaultSets"))
			foreach ($defaultSets as $setName => $setConfig)
				$this->addSet($setName, $setConfig);

		// Adds cherrycake sets
		$this->addSet(
			"cherrycakemain",
			[
				"directory" => LIB_DIR."/res/javascript/main"
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
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CHERRYCAKE,
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
	 * getSetUrl
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
		foreach ($setNames as $setName)
			$parameterSetNames .= $setName."-";
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
		if (!$this->sets[$setName] ?? false && is_array($this->sets[$setName]["files"]) && in_array($fileName, $this->sets[$setName]["files"]))
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
		$this->sets[$setName]["appendJavascript"] .= $javascript;
	}

	/**
	 * dump
	 *
	 * Outputs the requested Javascript sets to the client.
	 * It requests all UiComponent objects (if any) in the Ui module to add their own Javascript code or to include their needed Javascript files.
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

		if ($e->Ui && $e->Ui->uiComponents)
			foreach ($e->Ui->uiComponents as $UiComponent)
				$UiComponent->addCssAndJavascript();

		if ($this->GetConfig("isCache")) {
			$cacheProviderName = $this->GetConfig("cacheProviderName");
			$cacheTtl = $this->GetConfig("cacheTtl");

			// Build cache key
			$cacheKey = $e->Cache->buildCacheKey([
				"prefix" => $this->GetConfig("cachePrefix"),
				"uniqueId" => $request->set."_".$this->getConfig("lastModifiedTimestamp")
			]);

			if ($javascript = $e->Cache->$cacheProviderName->get($cacheKey)) {
				$e->Output->setResponse(new \Cherrycake\ResponseApplicationJavascript([
					"payload" => $javascript
				]));
				return;
			}
		}

		$requestedSetNames = explode("-", $request->set);

		$javascript = "";
		foreach($requestedSetNames as $requestedSetName) {
			if (!$requestedSet = $this->sets[$requestedSetName])
				continue;

			if (isset($requestedSet["files"])) {
				$parsed = [];
				foreach ($requestedSet["files"] as $file) {
					if (in_array($file, $parsed))
						continue;
					else
						$parsed[] = $file;
					
					$javascript .= $e->Patterns->parse(
						$file,
						[
							"directoryOverride" => $requestedSet["directory"] ?? false,
							"fileToIncludeBeforeParsing" => $requestedSet["variablesFile"] ?? false
						]
					)."\n";
				}
			}

			if (isset($requestedSet["appendJavascript"]))
				$javascript .= $requestedSet["appendJavascript"];

			// Include variablesFile specified files
			if (isset($requestedSet["variablesFile"]))
				if (is_array($requestedSet["variablesFile"]))
					foreach ($requestedSet["variablesFile"] as $fileName)
						include($fileName);
				else
					include($requestedSet["variablesFile"]);
		}

		// Final call to executeDeferredInlineJavascript function that executes all deferred inline javascript when everything else is loaded
		$javascript .= "if (typeof obj === 'executeDeferredInlineJavascript') executeDeferredInlineJavascript();";

		if($this->getConfig("isMinify"))
			$javascript = $this->minify($javascript);

		if ($this->GetConfig("isCache"))
			$e->Cache->$cacheProviderName->set($cacheKey, $javascript, $cacheTtl);

		$e->Output->setResponse(new \Cherrycake\ResponseApplicationJavascript([
			"payload" => $javascript
		]));
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