<?php

namespace Cherrycake\Css;

/**
 * Module that manages Css code.
 *
 * @package Cherrycake
 * @category Modules
 */
class Css extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"defaultSetOrder" => 100, // The default order to assign to sets when no order is specified
		"cacheTtl" => \Cherrycake\CACHE_TTL_LONGEST, // The TTL to use for the cache
		"cacheProviderName" => "engine", // The name of the cache provider to use
		"lastModifiedTimestamp" => false, // The timestamp of the last modification to the CSS files, or any other string that will serve as a unique identifier to force browser cache reloading when needed.
		"isHttpCache" => false, // Whether to send HTTP Cache headers or not
		"httpCacheMaxAge" => \Cherrycake\CACHE_TTL_LONGEST, // The TTL of the HTTP Cache
		"isMinify" => false, // Whether to minify the CSS code or not
		"responsiveWidthBreakpoints" => [
			"tiny" => 500,
			"small" => 700,
			"normal" => 980,
			"big" => 1300,
			"huge" => 1700
		]
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Errors",
		"Actions",
		"Cache",
		"Patterns",
		"HtmlDocument"
	];

	/**
	 * @var array $sets Contains an array of sets of Css files
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
	 * Maps the Actions to which this module must respond
	 */
	public static function mapActions() {
		global $e;
		$e->Actions->mapAction(
			"css",
			new \Cherrycake\Actions\ActionCss(
				moduleType: \Cherrycake\ACTION_MODULE_TYPE_CORE,
				moduleName: "Css",
				methodName: "dump",
				request: new \Cherrycake\Actions\Request(
					pathComponents: [
						new \Cherrycake\Actions\RequestPathComponent(
							type: \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							string: "css"
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
	 * @param $setName
	 * @param $setConfig
	 */
	function addSet($setName, $setConfig) {
		$this->sets[$setName] = $setConfig;
	}

	/**
	 * Builds HTML headers to request the given sets contents.
	 *
	 * @param array|string $setNames Name of the Css set, or an array of them. If set to false, all available sets are used.
	 * @return string The HTML header of the Css set
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
			$r .= '<link rel="stylesheet" href="'.$this->getSetUrl($setName).'">'."\n";
		return $r;
	}

	/**
	 * Builds a URL to request the given set contents.
	 * @param string $setName Name of the Javascript set
	 * @return string The Url of the Css requested sets
	 */
	function getSetUrl(string $setName): string {
		global $e;

		if (!is_array($this->sets))
			return null;

		return $e->Actions->getAction("css")->request->buildUrl(
			parameterValues: [
				"set" => $setName,
				"version" => ($this->getConfig("isCache") ? $this->getConfig("lastModifiedTimestamp") : uniqid())
			]
		);
	}

	/**
	 * Adds a file to a Css set
	 *
	 * @param string $setName The name of the set.
	 * @param string $fileName The file name, relative to the set's configured directory.
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
	 * addCssToSet
	 *
	 * Adds the specified Css to a set
	 *
	 * @param string $setName The name of the set
	 * @param string $css The Css
	 */
	function addCssToSet($setName, $css) {
		$this->sets[$setName]["appendCss"] = ($this->sets[$setName]["appendCss"] ?? false ?: "").$css;
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
			"prefix" => "cssParsedSet",
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
					description: "Couldn't open CSS directory",
					variables: [
						"setName" => $setName,
						"directory" => $requestedSet["directory"]
					]
				);
			}
			if ($handler = opendir($requestedSet["directory"])) {
				while (false !== ($entry = readdir($handler))) {
					if (substr($entry, -4) == ".css")
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
	 * @return string The parsed set, or false if something went wrong
	 */
	function parseSet($setName) {
		global $e;

		if (!isset($this->sets[$setName]))
			return null;

		if ($e->isDevel())
			$develInformation = "\nSet \"".$setName."\":\n";

		$requestedSet = $this->sets[$setName];

		$css = "";

		$files = $this->getSetFiles($setName);

		if ($files) {
			$parsed = [];
			$isScss = false;
			foreach ($files as $file) {
				if (in_array($file, $parsed))
					continue;
				else
					$parsed[] = $file;

				if ($e->isDevel())
					$develInformation .= $requestedSet["directory"]."/".$file."\n";

				$css .= $e->Patterns->parse(
					$file,
					directoryOverride: $requestedSet["directory"] ?? false,
					fileToIncludeBeforeParsing: $requestedSet["variablesFile"] ?? false
				)."\n";

				$fileExtension = strtolower(substr($file, strrpos($file, '.') + 1));
				if ($fileExtension === "scss")
					$isScss = true;
			}

			if ($isScss) {
				try {
					$scssCompiler = new \ScssPhp\ScssPhp\Compiler();
					$scssCompiler->setOutputStyle($e->isDevel() ? \ScssPhp\ScssPhp\OutputStyle::EXPANDED : \ScssPhp\ScssPhp\OutputStyle::COMPRESSED);
					$css = $scssCompiler->compileString($css)->getCss();
				}
				catch (\ScssPhp\ScssPhp\Exception\SassException $error) {
					$e->Errors->trigger(
						type: \Cherrycake\ERROR_SYSTEM,
						description: $error->getMessage()
					);
				}
			}
		}

		if (isset($requestedSet["appendCss"]))
			$css .=
				($e->isDevel() ? "\n/* ".$setName." appended CSS */\n\n" : null).
				$requestedSet["appendCss"];

		// Include variablesFile specified files
		if (isset($requestedSet["variablesFile"]))
			if (is_array($requestedSet["variablesFile"]))
				foreach ($requestedSet["variablesFile"] as $fileName)
					include($fileName);
			else
				include($requestedSet["variablesFile"]);

		if($this->getConfig("isMinify"))
			$css = $this->minify($css);

		if ($e->isDevel())
			$css = "/*\n".$develInformation."\n*/\n\n".$css;

		return $css;
	}

	/**
	 * Outputs the requested CSS sets to the client.
	 * It guesses what CSS sets to dump via the "set" get parameter.
	 * It handles CSS caching,
	 * Intended to be called from a <link rel ...>
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

		$css = '';

		$cacheKey = $e->Cache->buildCacheKey([
			"prefix" => "cssParsedSet",
			"uniqueId" => $request->set.'_'.$this->getConfig("lastModifiedTimestamp")
		]);

		if ($this->getConfig("isCache") && $e->Cache->$cacheProviderName->isKey($cacheKey))
			$css = $e->Cache->$cacheProviderName->get($cacheKey);
		else {
			$css = $this->parseSet($request->set);
			$e->Cache->$cacheProviderName->set(
				$cacheKey,
				$css,
				$this->GetConfig("cacheTtl")
			);
		}

		$e->Output->setResponse(new \Cherrycake\Actions\ResponseTextCss(payload: $css));
		return;
	}

	/**
	 * minify
	 *
	 * Minifies CSS code
	 *
	 * @param string $css The Css to minify
	 * @return string The minified Css
	 */
	function minify($css) {
		$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
		$css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
		return $css;
	}

	/**
	 * unit
	 *
	 * @param int $value The value
	 * @param string $unit The unit. "px" by default.
	 * @return string The value in the specified units
	 */
	function unit($value, $unit = "px") {
		switch ($unit) {
			case "%":
				return number_format($value, 2, ".", "")."%";

			default:
				return $value.$unit;
		}
	}

	/**
	 * getFileDataBase64
	 *
	 * @param string $fileName The name of the file
	 * @return string A Base64 representation of the specified file apt for Css inclusion.
	 */
	function getFileDataBase64($fileName) {
		$r = "data:";

		list(,, $image_type) = getimagesize($fileName);

		switch($image_type) {
			case IMAGETYPE_GIF:
				$r .= "image/gif";
				break;

			case IMAGETYPE_JPEG:
				$r .= "image/jpeg";
				break;

			case IMAGETYPE_PNG:
				$r .= "image/png";
				break;

			default:
				switch(strtolower(substr(strrchr($fileName, "."), 1)))
				{
					case "svg":
						$r .= "image/svg+xml";
						break;

					case "woff":
						$r .= "font/opentype";
						break;

					case "ttf":
						$r .= "font/ttf";
						break;

					case "eot":
						$r .= "font/eot";
						break;
				}
				break;
		}

		$r .= ";base64,";
		$r .= base64_encode(file_get_contents($fileName));

		return $r;
	}

	/**
	 * buildBackgroundImageForElement
	 *
	 * Builds the Css code to apply a background image.
	 * Supports HD version images for high density displays by using a media query
	 * Supports both textures and single images
	 *
	 * @param array $setup Setup options
	 * @return string The Css code
	 */
	function buildBackgroundImageForElement($setup) {
		if (!$setup["type"])
			$setup["type"] = "texture";

		if (!$setup["imageUrlHd"]) {
			$imagePathInfo = pathinfo($setup["imageUrl"]);
			$setup["imageUrlHd"] = $imagePathInfo["dirname"]."/".$imagePathInfo["filename"]."@2x.".$imagePathInfo["extension"];
		}

		$r =
			$setup["selector"]."{".
				"background-image: url('".$setup["imageUrl"]."');".
				($setup["type"] == "texture" ? "background-repeat: repeat;" : null).
			"}";

		if (file_exists(".".$setup["imageUrlHd"]))
			if ($imageSize = getimagesize(".".$setup["imageUrl"])) {
				$r .=
					"@media (min--moz-device-pixel-ratio: 1.5), (-o-min-device-pixel-ratio: 3/2), (-webkit-min-device-pixel-ratio: 1.5), (min-device-pixel-ratio: 1.5), (min-resolution: 144dpi), (min-resolution: 1.5dppx) {".
						$setup["selector"]."{".
							"background-image: url('".$setup["imageUrlHd"]."');".
							"background-size: ".$imageSize[0]."px ".$imageSize[1]."px;".
						"}".
					"}";
			}

		return $r;
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
				$r[$setName]["files"][] = $set["directory"]."/*.css";

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
