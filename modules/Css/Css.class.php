<?php

/**
 * Css
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const CSS_MEDIAQUERY_TABLET = 0; // Matches tablets in all orientations
const CSS_MEDIAQUERY_TABLET_PORTRAIT = 1; // Matches tablets in portrait orientation
const CSS_MEDIAQUERY_TABLET_LANDSCAPE = 2; // Matches tablets in landscape orientation
const CSS_MEDIAQUERY_PHONE = 3; // Matches phones in all orientations
const CSS_MEDIAQUERY_PHONE_PORTRAIT = 4; // Matches phones in portrait orientation
const CSS_MEDIAQUERY_PHONE_LANDSCAPE = 5; // Matches phones in landscape orientation
const CSS_MEDIAQUERY_PORTABLES = 6; // Matches all portable devices and any other small-screen devices (tablets, phones and similar) in all orientations

/**
 * Css
 *
 * Module that manages Css code.
 *
 * * It works nicely in conjunction with HtmlDocument module.
 * * Css code minifying.
 * * Multiple Css files are loaded in just one request.
 * * Treats Css files as patterns in conjunction with Patterns module, allowing the use of calls to the engine within Css code, PHP programming structures, variables, etc.
 * * Implements Css code caching in conjunction with Cache module.
 * * Implements "file sets"
 * * Implements a simple code version mechanism that helps avoiding client-side caching problems.
 *
 * Configuration example for css.config.php:
 * <code>
 * $cssConfig = [
 * 	"defaultDirectory" => "res/css", // The default directory where CSS files in each CSS set will be searched
 *  "cachePrefix" => "Css", // The prefix to use for storing CSS on the cache
 * 	"cacheTtl" => \Cherrycake\Modules\CACHE_TTL_LONGEST, // The cache TTL for CSS sets
 * 	"cacheProviderName" => "fast", // The cache provider for CSS sets
 * 	"lastModifiedTimestamp" => 1, // The last modified timestamp of CSS, to handle caches and http cache
 *  "isCache" => false, // Whether to use cache or not
 *  "isHttpCache" => false, // Whether to send HTTP Cache headers or not
 *  "httpCacheMaxAge" => false, // The maximum age in seconds for HTTP Cache
 *  "isMinify" => true, // Whether to minify the resulting CSS or not
 *  "responsiveWidthBreakpoints" => [ // The different considered responsive widths
 *      "tiny" => 500,
 *      "small" => 700,
 *      "normal" => 980,
 *      "big" => 1300,
 *      "huge" => 1700
 *  ],
 * 	"defaultSets" => [] // The CSS sets available to be included in HTML documents
 * 		"main" => [
 * 			"directory" => "res/css/main", // The specific directory where the CSS files for this set reside
 * 			"variablesFile" => "res/css/cssvariables.php", // A file to include whenever parsing this set files, usually for defining variables that can be later used inside the css files
 * 			"files" => [ // The files that this CSS set contain
 * 				"main.css",
 * 				"header.css",
 * 				"content.css"
 * 			]
 * 		],
 *		"UiComponents" => [ // This set must be declared when working with Ui module
 *			"version" => 1,
 *			"directory" => "res/css/UiComponents",
 *			"files" => [ // The default Ui-related Css files, these are normally the ones that are not bonded to an specific UiComponent, since any other required file is automatically added here by the specific UiComponent object.
 *			]
 *		]
 * 	]
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Css extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"cachePrefix" => "Css",
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
		"HtmlDocument",
		"Ui"
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
				"directory" => LIB_DIR."/res/css/main"
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
			"css",
			new \Cherrycake\ActionCss([
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CHERRYCAKE,
				"moduleName" => "Css",
				"methodName" => "dump",
				"request" => new \Cherrycake\Request([
					"pathComponents" => [
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "css"
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
	 * @param mixed $setNames The name of the Css set, or an array of them
	 * @return string The Url of the Css set
	 */
	function getSetUrl($setNames) {
		global $e;

		if (!$e->Actions->getAction("css"))
			return false;

		if (!is_array($setNames))
			$setNames = [$setNames];

		$parameterSetNames = "";
		foreach ($setNames as $setName)
			$parameterSetNames .= $setName."-";
		$parameterSetNames = substr($parameterSetNames, 0, -1);
		
		return $e->Actions->getAction("css")->request->buildUrl([
			"parameterValues" => [
				"set" => $parameterSetNames,
				"version" => $this->getConfig("lastModifiedTimestamp")
			]
		]);
	}

	/**
	 * addFileToSet
	 *
	 * Adds a file to a Css set
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
	 * addCssToSet
	 *
	 * Adds the specified Css to a set
	 *
	 * @param string $setName The name of the set
	 * @param string $css The Css
	 */
	function addCssToSet($setName, $css) {
		$this->sets[$setName]["appendCss"] = ($this->sets[$setName]["appendCss"] ?? null).$css;
	}

	/**
	 * dump
	 *
	 * Outputs the requested CSS sets to the client.
	 * It requests all UiComponent objects (if any) in the Ui module to add their own Css code or to include their needed Css files
	 * It guesses what CSS sets to dump via the "set" get parameter.
	 * It handles CSS caching,
	 * Intended to be called from a <link rel ...>
	 *
	 * @param Request $request The Request object received
	 */
	function dump($request) {
		global $e;

		if ($this->getConfig("isHttpCache"))
			\Cherrycake\HttpCache::init($this->getConfig("lastModifiedTimestamp"), $this->getConfig("httpCacheMaxAge"));

		if ($e->Ui && $e->Ui->uiComponents)
			foreach ($e->Ui->uiComponents as $UiComponent) {
				$UiComponent->addCssAndJavascript();
			}

		if ($this->GetConfig("isCache")) {
			$cacheProviderName = $this->GetConfig("cacheProviderName");
			$cacheTtl = $this->GetConfig("cacheTtl");

			// Build cache key
			$cacheKey = $e->Cache->buildCacheKey([
				"prefix" => $this->GetConfig("cachePrefix"),
				"uniqueId" => $request->set."_".$this->getConfig("lastModifiedTimestamp")
			]);

			if ($css = $e->Cache->$cacheProviderName->get($cacheKey)) {
				$e->Output->setResponse(new \Cherrycake\ResponseTextCss([
					"payload" => $css
				]));
				return;
			}
		}

		$requestedSetNames = explode("-", $request->set);

		$css = "";
		foreach($requestedSetNames as $requestedSetName) {

			if (!$requestedSet = $this->sets[$requestedSetName])
				continue;

			if ($requestedSet["files"] ?? false && is_array($requestedSet["files"])) {
				$parsed = [];
				foreach ($requestedSet["files"] as $file) {
					if (in_array($file, $parsed))
						continue;
					else
						$parsed[] = $file;
					
					$css .= $e->Patterns->parse(
						$file,
						[
							"directoryOverride" => $requestedSet["directory"] ?? false,
							"fileToIncludeBeforeParsing" => $requestedSet["variablesFile"] ?? false
						]
					)."\n";
				}
			}

			if (isset($requestedSet["appendCss"]))
				$css .= $requestedSet["appendCss"];

			// Include variablesFile specified files
			if (isset($requestedSet["variablesFile"]))
				if (is_array($requestedSet["variablesFile"]))
					foreach ($requestedSet["variablesFile"] as $fileName)
						include($fileName);
				else
					include($requestedSet["variablesFile"]);

			if (isset($requestedSet["isGenerateTextColorsCssHelpers"]) && isset($textColors))
				$css .= $this->generateCssHelperTextColors($textColors);

			if (isset($requestedSet["isGenerateBackgroundColorsCssHelpers"]) && isset($backgroundColors))
				$css .= $this->generateCssHelperBackgroundColors($backgroundColors);

			if (isset($requestedSet["isGenerateBackgroundGradientsCssHelpers"]) && isset($gradients))
				$css .= $this->generateCssHelperBackgroundGradients($gradients);
		}

		if($this->getConfig("isMinify"))
			$css = $this->minify($css);

		if ($this->GetConfig("isCache"))
			$e->Cache->$cacheProviderName->set($cacheKey, $css, $cacheTtl);

		$e->Output->setResponse(new \Cherrycake\ResponseTextCss([
			"payload" => $css
		]));
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
	 * generateCssHelperTextColors
	 *
	 * @param $colors The colors array to use
	 * @return string Text colors Css helper code
	 */
	function generateCssHelperTextColors($colors) {
		while (list($colorName, $color) = each($colors))
			$r .= ".textColor_".$colorName." { color: ".$color." !important; }\n";

		return $r;
	}

	/**
	 * generateCssHelperBackgroundColors
	 *
	 * @param $colors The colors array to use
	 * @return string Background colors Css helper code
	 */
	function generateCssHelperBackgroundColors($colors) {
		while (list($colorName, $color) = each($colors))
			$r .= ".backgroundColor_".$colorName." { background-color: ".$color." !important; }\n";

		return $r;
	}

	/**
	 * generateCssHelperBackgroundGradients
	 *
	 * @param $gradients The gradients array to use
	 * @return string Background gradients Css helper code
	 */
	function generateCssHelperBackgroundGradients($gradients) {
		while (list($gradientName, $gradient) = each($gradients))
			$r .= ".backgroundGradient_".$gradientName." { ".$gradient->getCssBackground()." }\n";

		return $r;
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
	 * clearFix
	 *
	 * @param string $selector The Css selector of the element to apply clearfix to
	 * @return string Css to apply clearfix to the specified element
	 */
	function clearFix($selector) {
		return
			$selector.":before,\n".$selector.":after {\ncontent: \"\";\ndisplay: table;\n}\n".$selector.":after {\nclear: both;\n}\n".$selector." {\nzoom: 1;\n}\n";
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
	 * buildUnsupportedProperty
	 *
	 * Returns CSS to support all equivalents of $baseParameter with given $value
	 *
	 * @param string $baseParameter The name of the Css property, i.e: "linear-gradient"
	 * @param string $value The value
	 * @return string The resulting CSS to support all variants for different browser engines
	 */
	function buildUnsupportedProperty($baseParameter, $value) {
		global $e;

		// Consider exceptions
		if ($baseParameter == "border-top-left-radius")
			$baseParameterForGecko = "border-radius-topleft";

		if ($baseParameter == "border-top-right-radius")
			$baseParameterForGecko = "border-radius-topright";

		if ($baseParameter == "border-bottom-left-radius")
			$baseParameterForGecko = "border-radius-bottomleft";

		if ($baseParameter == "border-bottom-right-radius")
			$baseParameterForGecko = "border-radius-bottomright";

		
		$baseParameterForWebkit = $baseParameter;
		$baseParameterForGecko = $baseParameter;
		$baseParameterForPresto = $baseParameter;
		$baseParameterForStandardCompliant = $baseParameter;

		return
			"-webkit-".$baseParameterForWebkit.": ".$value.";\n".
			"-moz-".$baseParameterForGecko.": ".$value.";\n".
			"-o-".$baseParameterForPresto.": ".$value.";\n".
			$baseParameterForStandardCompliant.": ".$value.";\n";
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
	 * mediaQuery
	 *
	 * Returns a CSS media query aimed to match devices with specific characteristics, or different maximum screen widths
	 *
	 * @param array $setup The specific characteristics for this media query to match, in the syntax:
	 *  - css: The CSS code for this media query
	 *  - predefined: One of the available CSS_MEDIAQUERY_* consts (Optional)
	 *  - maxWidthBreakpoint: One of the configured widths on the config variable responsiveWidthBreakpoints to be used as a max width breakpoint for the generated media query (The CSS will take effect when the page width is the specified width or less)
	 *  - minWidthBreakpoint: One of the configured widths on the config variable responsiveWidthBreakpoints to be used as a min width breakpoint for the generated media query (The CSS will take effect when the page width is the specified width or more)
	 *  - characteristics: A key-value array with the specific characteristics for this media query, for example:
	 *      "min-device-width" => "768px",
	 *      "max-device-width" => "1024px",
	 *      "-webkit-min-device-pixel-ratio" => 2,
	 *      "orientation" => "portrait"
	 * @return string The Css
	 */
	function mediaQuery($setup) {
		if (isset($setup["predefined"])) {
			switch ($setup["predefined"]) {
				case CSS_MEDIAQUERY_TABLET:
					$setup["characteristics"] = [
						"min-device-width" => "768px",
						"max-device-width" => "1024px"
					];
					break;
				case CSS_MEDIAQUERY_TABLET_PORTRAIT:
					$setup["characteristics"] = [
						"min-device-width" => "768px",
						"max-device-width" => "1024px",
						"orientation" => "portrait"
					];
					break;
				case CSS_MEDIAQUERY_TABLET_LANDSCAPE:
					$setup["characteristics"] = [
						"min-device-width" => "768px",
						"max-device-width" => "1024px",
						"orientation" => "landscape"
					];
					break;
				case CSS_MEDIAQUERY_PHONE:
					$setup["characteristics"] = [
						"min-device-width" => "320px",
						"max-device-width" => "568px"
					];
					break;
				case CSS_MEDIAQUERY_PHONE_PORTRAIT:
					$setup["characteristics"] = [
						"min-device-width" => "320px",
						"max-device-width" => "568px",
						"orientation" => "portrait"
					];
					break;
				case CSS_MEDIAQUERY_PHONE_LANDSCAPE:
					$setup["characteristics"] = [
						"min-device-width" => "320px",
						"max-device-width" => "568px",
						"orientation" => "landscape"
					];
					break;
				case CSS_MEDIAQUERY_PORTABLES:
					$setup["characteristics"] = [
						"min-device-width" => "320px",
						"max-device-width" => "1024px"
					];
					break;
			}
		}
		else {
			if (isset($setup["maxWidthBreakpoint"]))
				$setup["characteristics"] = [
					"max-width" => $this->unit($this->getConfig("responsiveWidthBreakpoints")[$setup["maxWidthBreakpoint"]], "px")
				];
			if (isset($setup["minWidthBreakpoint"]))
				$setup["characteristics"] = [
					"min-width" => $this->unit($this->getConfig("responsiveWidthBreakpoints")[$setup["minWidthBreakpoint"]], "px")
				];

			if (isset($setup["maxWidth"]))
				$setup["characteristics"] = [
					"max-width" => $this->unit($setup["maxWidth"], "px")
				];
			if (isset($setup["minWidth"]))
				$setup["characteristics"] = [
					"min-width" => $this->unit($setup["minWidth"], "px")
				];
		}

		$r = "@media only screen";
		foreach ($setup["characteristics"] as $key => $value)
			$r .= " and (".$key.": ".$value.")";
		$r .= " { ".$setup["css"]." }\n";

		return $r;
	}

}