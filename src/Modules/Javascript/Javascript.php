<?php

namespace Cherrycake\Modules\Javascript;

use Cherrycake\Engine;
use Cherrycake\Modules\Cache\Cache;

/**
 * Module that manages Javascript code.
 */
class Javascript extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"defaultSetOrder" => 100, // The default order to assign to sets when no order is specified
		"cacheProviderName" => "engine", // The name of the cache provider to use
		"cacheTtl" => Cache::TTL_LONGEST,
		"lastModifiedTimestamp" => false, // The timestamp of the last modification to the JavaScript files, or any other string that will serve as a unique identifier to force browser cache reloading when needed
		"isHttpCache" => false, // Whether to send HTTP Cache headers or not
		"httpCacheMaxAge" => Cache::TTL_LONGEST, //  The TTL of the HTTP Cache
		"isMinify" => false // Whether to minify the JavaScript code or not
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
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
	function init(): bool {
		if (!parent::init())
			return false;

		if ($sets = $this->getConfig("sets"))
			foreach ($sets as $setName => $setConfig)
				$this->addSet($setName, $setConfig);

		return true;
	}

	/**
	 * mapActions
	 *
	 * Maps the Actions to which this module must respond
	 */
	public static function mapActions() {
		Engine::e()->Actions->mapAction(
			"javascript",
			new \Cherrycake\Modules\Actions\ActionJavascript(
				moduleType: \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_CORE,
				moduleName: "Javascript",
				methodName: "dump",
				request: new \Cherrycake\Modules\Actions\Request(
					pathComponents: [
						new \Cherrycake\Modules\Actions\RequestPathComponent(
							type: \Cherrycake\Modules\Actions\Request::PATH_COMPONENT_TYPE_FIXED,
							string: "js"
						)
					],
					parameters: [
						new \Cherrycake\Modules\Actions\RequestParameter(
							name: "set",
							type: \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_GET
						),
						new \Cherrycake\Modules\Actions\RequestParameter(
							name: "version",
							type: \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_GET
						)
					]
				)
			)
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
	 * Builds HTML headers to request the given sets contents.
	 *
	 * @param array|string $setNames Name of the Javascript set, or an array of them.
	 * @return string The HTML header of the Javascript set
	 */
	function getSetsHtmlHeaders($setNames = false) {
		if (!$setNames)
			return;

		if (!is_array($setNames))
			$setNames = [$setNames];

		if (!count($setNames))
			return;

		$r = '';
		foreach ($setNames as $setName)
			$r .= '<script type="'.($this->sets[$setName]['type'] ?? false ?: 'text/javascript').'" src="'.$this->getSetUrl($setName).'"></script>'."\n";
		return $r;
	}

	/**
	 * Builds a URL to request the given set contents.
	 * @param string $setName Name of the Javascript set
	 * @return string The Url of the Javascript set
	 */
	function getSetUrl(string $setName): string {

		if (!is_array($this->sets))
			return null;

		return Engine::e()->Actions->getAction("javascript")->request->buildUrl(
			parameterValues: [
				"set" => $setName,
				"version" => ($this->getConfig("isCache") ? $this->getConfig("lastModifiedTimestamp") : uniqid())
			]
		);
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
		// Get the unique id for each set with its currently added files and see if it's in cache. If it's not, add it to cache.
		$cacheProviderName = $this->GetConfig("cacheProviderName");
		$cacheTtl = $this->GetConfig("cacheTtl");
		$cacheKey = Engine::e()->Cache->buildCacheKey(
			prefix: "javascriptParsedSet",
			setName: $setName,
			uniqueId: $this->getSetUniqueId($setName)
		);
		if (!Engine::e()->Cache->$cacheProviderName->isKey($cacheKey))
			Engine::e()->Cache->$cacheProviderName->set(
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

		$requestedSet = $this->sets[$setName];

		if ($requestedSet["isIncludeAllFilesInDirectory"] ?? false) {
			if (Engine::e()->isDevel() && !is_dir($requestedSet["directory"])) {
				Engine::e()->Errors->trigger(
					type: Errors::ERROR_SYSTEM,
					description: "Couldn't open JavaScript directory",
					variables: [
						"setName" => $setName,
						"directory" => $requestedSet["directory"]
					]
				);
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

		if (!isset($this->sets[$setName]))
			return null;

		if (Engine::e()->isDevel())
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

				if (Engine::e()->isDevel())
					$develInformation .= $requestedSet["directory"]."/".$file."\n";

				$js .= Engine::e()->Patterns->parse(
					$file,
					directoryOverride: $requestedSet["directory"] ?? false,
					fileToIncludeBeforeParsing: $requestedSet["variablesFile"] ?? false
				)."\n";
			}
		}

		if (isset($requestedSet["appendJavascript"]))
			$js .=
				(Engine::e()->isDevel() ? "\n/* ".$setName." appended JavaScript */\n\n" : null).
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

		if (Engine::e()->isDevel())
			$js = "/*\n".$develInformation."\n*/\n\n".$js;

		return $js;
	}


	/**
	 * Outputs the requested Javascript sets to the client.
	 * It guesses what Javascript sets to dump via the "set" get parameter.
	 * It handles Javascript caching,
	 * Intended to be called from a <script src ...>
	 * @param Request $request The Request object received
	 */
	function dump($request) {

		if ($this->getConfig("isHttpCache"))
			\Cherrycake\HttpCache::init($this->getConfig("lastModifiedTimestamp"), $this->getConfig("httpCacheMaxAge"));

		if (!$request->set) {
			Engine::e()->Output->setResponse(new \Cherrycake\Modules\Actions\ResponseTextCss);
			return;
		}

		$cacheProviderName = $this->GetConfig("cacheProviderName");

		$js = '';

		$cacheKey = Engine::e()->Cache->buildCacheKey(
			prefix: "javascriptParsedSet",
			uniqueId: $request->set.'_'.$this->getConfig("lastModifiedTimestamp")
		);

		if ($this->getConfig("isCache") && Engine::e()->Cache->$cacheProviderName->isKey($cacheKey))
			$js = Engine::e()->Cache->$cacheProviderName->get($cacheKey);
		else {
			$js = $this->parseSet($request->set);
			Engine::e()->Cache->$cacheProviderName->set(
				$cacheKey,
				$js,
				$this->GetConfig("cacheTtl")
			);
		}

		// Final call to executeDeferredInlineJavascript function that executes all deferred inline javascript when everything else is loaded
		$js .= "if (typeof obj === 'executeDeferredInlineJavascript') executeDeferredInlineJavascript();";

		Engine::e()->Output->setResponse(new \Cherrycake\Modules\Actions\ResponseApplicationJavascript(payload: $js));
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
		return $javascript;
		return \Cherrycake\Modules\JavascriptMinifier::minify($javascript);
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
			foreach ($this->sets as $setName => $set) {

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
