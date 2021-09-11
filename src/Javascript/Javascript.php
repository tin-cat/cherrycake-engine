<?php

namespace Cherrycake\Javascript;

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
		"cacheTtl" => \Cherrycake\CACHE_TTL_LONGEST,
		"lastModifiedTimestamp" => false, // The timestamp of the last modification to the JavaScript files, or any other string that will serve as a unique identifier to force browser cache reloading when needed
		"isHttpCache" => false, // Whether to send HTTP Cache headers or not
		"httpCacheMaxAge" => \Cherrycake\CACHE_TTL_LONGEST, //  The TTL of the HTTP Cache
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
		global $e;

		$e->Actions->mapAction(
			"javascript",
			new \Cherrycake\Actions\ActionJavascript(
				moduleType: \Cherrycake\ACTION_MODULE_TYPE_CORE,
				moduleName: "Javascript",
				methodName: "dump",
				request: new \Cherrycake\Actions\Request(
					pathComponents: [
						new \Cherrycake\Actions\RequestPathComponent(
							type: \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							string: "js"
						)
					],
					parameters: [
						new \Cherrycake\Actions\RequestParameter(
							name: "set",
							type: \Cherrycake\REQUEST_PARAMETER_TYPE_GET
						),
						new \Cherrycake\Actions\RequestParameter(
							name: "version",
							type: \Cherrycake\REQUEST_PARAMETER_TYPE_GET
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
		global $e;

		if (!is_array($this->sets))
			return null;

		return $e->Actions->getAction("javascript")->request->buildUrl(
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
				$e->Errors->trigger(
					type: \Cherrycake\ERROR_SYSTEM,
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
					directoryOverride: $requestedSet["directory"] ?? false,
					fileToIncludeBeforeParsing: $requestedSet["variablesFile"] ?? false
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
	 * Outputs the requested Javascript sets to the client.
	 * It guesses what Javascript sets to dump via the "set" get parameter.
	 * It handles Javascript caching,
	 * Intended to be called from a <script src ...>
	 * @param Request $request The Request object received
	 */
	function dump($request) {
		global $e;

		if ($this->getConfig("isHttpCache"))
			\Cherrycake\HttpCache::init($this->getConfig("lastModifiedTimestamp"), $this->getConfig("httpCacheMaxAge"));

		if (!$request->set) {
			$e->Output->setResponse(new \Cherrycake\Actions\ResponseTextCss);
			return;
		}

		$cacheProviderName = $this->GetConfig("cacheProviderName");

		$js = '';

		$cacheKey = $e->Cache->buildCacheKey([
			"prefix" => "javascriptParsedSet",
			"uniqueId" => $request->set.'_'.$this->getConfig("lastModifiedTimestamp")
		]);

		if ($this->getConfig("isCache") && $e->Cache->$cacheProviderName->isKey($cacheKey))
			$js = $e->Cache->$cacheProviderName->get($cacheKey);
		else {
			$js = $this->parseSet($request->set);
			$e->Cache->$cacheProviderName->set(
				$cacheKey,
				$js,
				$this->GetConfig("cacheTtl")
			);
		}

		// Final call to executeDeferredInlineJavascript function that executes all deferred inline javascript when everything else is loaded
		$js .= "if (typeof obj === 'executeDeferredInlineJavascript') executeDeferredInlineJavascript();";

		$e->Output->setResponse(new \Cherrycake\Actions\ResponseApplicationJavascript(payload: $js));
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
