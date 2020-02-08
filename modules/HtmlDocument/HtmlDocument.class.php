<?php

/**
 * HtmlDocument
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const HTML_RESPONSE_CODE_NOT_FOUND = 404;
const HTML_RESPONSE_CODE_NO_PERMISSION = 403;
const HTML_RESPONSE_CODE_INTERNAL_SERVER_ERROR = 500;

/**
 * HtmlDocument
 *
 * Provides basic tools to build correctly formatted and SEO optimized HTML5 documents
 *
 * Configuration example for htmldocument.config.php:
 * <code>
 * $htmlDocumentConfig = [
 * 	"title" => "Cherrycake test", // The default page title
 * 	"description" => "A test application built with the Cherrycake engine", // The default page description
 * 	"copyright" => "Copyright Cherrycake ".date("Y"), // The default page copyright info
 * 	"keywords" => ["Cherrycake", "Engine", "Test"], // The default page keywords
 * 	"isAllowRobotsIndex" => false, // Whether to allow robots to index the document
 * 	"isAllowRobotsFollow" => false, // Whether to allow robots to follow links on the document
 *  "googleAnalyticsTrackingId" => false, // Set it to the Google Analytics Tracking Id (UA-999999-99) to setup GA Statistics. Leave it to false to not use Google Analytics. Only added when not in devel environment.
 *  "matomoTrackingId" => false, // Set it to the Matomo Analytics Tracking Id. Leave it to false to not use Matomo. Only added when not in devel environment.
 *  "matomoServerUrl" => false, // When using Matomo, set it to the Matomo¡s server url  
 *	"defaultCssSetsToInclude" => [] // The CSS sets that will be included by default in the document if none specified. Each element of the array corresponds to one <link rel ...> in the html document (use this logic to combine version caching capabilities while reducing the number of different CSS requests to be done by the client)
 *		"main"
 *	],
 *	"defaultJavascriptSetsToInclude" => [ // The Javascript sets that will be included by default in the document if none specified. Each element of the array corresponds to one <script src ...> in the html document (use this logic to combine version caching capabilities while reducing the number of different Javascript request to be done by the client)
 *		"main"
 *	],
 *  "mobileViewport" => [ // Configuration for the site when viewed in a mobile device, via the viewport meta
 *      "width" => 500, // The width of the viewport: A number of pixels, or "device-width"
 *      "userScalable" => false, // Optional, whether or not to let the user pinch to zoom in/out
 *      "initialScale" => 1, // Optional, the initial scale
 *      "maximumScale" => 2 // Optional, the maximum scale
 *  ],
 *  "isNoticeForOlderInternetExplorer" => false, // When set to true, adds a warning on the page for users visiting with an old Internet Explorer browser
 *  "isDeferJavascript" => false // Whether to defer javascript sets loading
 *  // Most of the following information and images can be automatically generated with http://realfavicongenerator.net/
 *  "microsoftApplicationInfo" => [ // Application info for Microsoft standards (i.e: When adding the web as a shortcut in Windows 8)
 *      "name" => "", // The name of the app
 *      "tileColor" => "", // The color of the tile on Windows 8, in HTML hexadecimal format (i.e: #dd2153)
 *      "tileImage" => "", // Path to an image to use as a tile image for Windows 8. Must be in png format
 *  ],
 *  "appleApplicationInfo" => [ // Application info for Apple standards (i.e: When adding the web as a shortcut in iOs devices, or to hint the users about the App store APP for this site)
 *      "name" => "", // The name of the app
 *      "iTunesAppId" => "", // The id of the related application on iTunes, if any
 *      "icons" => [ // Image SRCs for common icon sizes. Must be in png format
 *          "57x57" => "",
 *          "114x114" => "",
 *          "72x72" => "",
 *          "144x144" => "",
 *          "60x60" => "",
 *          "120x120" => "",
 *          "76x76" => "",
 *          "152x152" => ""
 *      ]
 *  ],
 *  "favIcons" => [ // Image SRCs for common favicon files. Must be in png format
 *      "196x196" => "",
 *      "160x160" => "",
 *      "96x96" => "",
 *      "16x16" => "",
 *      "32x32" => ""
 *  ]
 * );
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 * @todo Make html tag's lang parameter match the real language using the Locale module in header method
 * @todo Implement link rel="canonical" based on the Locale module config key "canonicalLocale" (info: https://support.google.com/webmasters/answer/139394?hl=es)
 */
class HtmlDocument extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"charset" => "utf-8",
		"bodyAdditionalCssClasses" => false,
		"isAllowRobotsIndex" => true,
		"isAllowRobotsFollow" => true,
		"isNoticeForOlderInternetExplorer" => false,
		"isDeferJavascript" => false
	];

	/**
	 * @var array $cssSets An array of Css sets that must be requested on this HTML document
	 */
	private $cssSets;

	/**
	 * @var array $javascriptSets An array of Javascript sets that must be requested on this HTML document
	 */
	private $javascriptSets;

	/**
	 * @var string $inlineJavascript Javascript code that must be executed inline from the HTML
	 */
	private $inlineJavascript;

	/**
	 * @var mixed $footerAdditionalHtml HTML code to be additionally added to the end of the document body
	 */
	private $footerAdditionalHtml = false;

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module, to be dumped on the header method
	 */
	var $dependentCherrycakeModules = [
		"Css",
		"Javascript"
	];

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

		if ($this->getConfig("defaultCssSetsToInclude"))
			foreach ($this->getConfig("defaultCssSetsToInclude") as $cssSetName)
				$this->addCssSet($cssSetName);

		if ($this->getConfig("defaultJavascriptSetsToInclude"))
			foreach ($this->getConfig("defaultJavascriptSetsToInclude") as $cssSetName)
				$this->addJavascriptSet($cssSetName);

		return true;
	}

	/**
	 * setTitle
	 *
	 * Sets the Html document's title
	 *
	 * @param string $title The title
	 */
	function setTitle($title) {
		$this->setConfig("title", $title);
	}

	/**
	 * setDescription
	 *
	 * Sets the Html document's description
	 *
	 * @param string $description The description
	 */
	function setDescription($description) {
		$this->setConfig("description", $description);
	}

	/**
	 * addKeywords
	 *
	 * Adds keywords to the document
	 *
	 * @param mixed $keywords An array of keywords or a single string keyword
	 */
	function addKeywords($keywords) {
		if (is_array($keywords))
			$this->setConfig("keywords", array_merge($this->getConfig("keywords"), $keywords));
		else
			$this->setConfig("keywords", array_merge($this->getConfig("keywords"), [$keywords]));
	}

	function addCssSet($setName) {
		if (!is_array($this->cssSets) || !in_array($setName, $this->cssSets))
			$this->cssSets[] = $setName;
	}

	function addJavascriptSet($setName) {
		if (!is_array($this->javascriptSets) || !in_array($setName, $this->javascriptSets))
			$this->javascriptSets[] = $setName;
	}

	/**
	 * addInlineJavascript
	 *
	 * Adds Javascript code to be executed inline on the HTML itself. If isDeferJavascript is true, this code will be executed only after Javascript sets have been loaded by the client
	 *
	 * @param $javascript
	 */
	function addInlineJavascript($javascript) {
		$this->inlineJavascript .= $javascript;
	}

	/**
	 * Adds HTML code to the end of the document body
	 * 
	 * @param string $html The HTML to add to the end of the body
	 */
	function addFooterAdditionalHtml($html) {
		$this->footerAdditionalHtml .= $html;
	}

	/**
	 * @return mixed The HTML code to be added to the end of the document body, or false if none specified.
	 */
	function getFooterAdditionalHtml() {
		return $this->footerAdditionalHtml;
	}

	/**
	 * header
	 *
	 * Returns a properly built HTML header
	 *
	 * @param array $setup Setup options to configure the HTML header, with possible keys:
	 * "bodyAdditionalCssClasses" => false // Additional CSS classes for the body element
	 */
	function header($setup = false) {
		global $e;

		$r = "<!DOCTYPE html>\n";

		$r .= "<html lang=\"en\">\n";

		$r .= "<head>\n";

		if ($charset = $this->getConfig("charset"))
			$r .= "<meta charset=\"".$charset."\" />\n";

		if ($title = $this->getConfig("title"))
			$r .= "<title>".$title."</title>\n";

		if ($description = $this->getConfig("description"))
			$r .= "<meta name=\"description\" content=\"".$description."\" />\n";

		if ($copyright = $this->getConfig("copyright"))
			$r .= "<meta name=\"copyright\" content=\"".$copyright."\" />\n";

		if (is_array($keywords = $this->getConfig("keywords")))
			$r .= "<meta name=\"keywords\" content=\"".implode(",", $keywords)."\" />\n";

		$r .=
			"<meta name=\"robots\" content=\"".
				($this->getConfig("isAllowRobotsIndex") ? "index" : "noindex").
				",".
				($this->getConfig("isAllowRobotsFollow") ? "follow" : "nofollow").
			"\" />\n";

		if ($iTunesAppId = $this->getConfig("iTunesAppId"))
			$r .= "<meta name=\"apple-itunes-app\" content=\"".$iTunesAppId."\" />\n";

		// Css
		if ($e->Css)
			if (is_array($this->cssSets))
				$r .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$e->Css->getSetUrl($this->cssSets)."\" />\n";

		// Javascript
		if ($e->Javascript)
			if (is_array($this->javascriptSets) && !$this->getConfig("isDeferJavascript"))
				$r .= "<script type=\"text/javascript\" src=\"".$e->Javascript->getSetUrl($this->javascriptSets)."\"></script>\n";

		// Mobile viewport
		if ($mobileViewport = $this->getConfig("mobileViewport")) {
			if (isset($mobileViewport["width"]))
				$mobileViewportItems[] = "width=".$mobileViewport["width"];

			if (isset($mobileViewport["userScalable"]))
				$mobileViewportItems[] = "user-scalable=".($mobileViewport["userScalable"] ? "yes" : "no");

			if (isset($mobileViewport["initialScale"]))
				$mobileViewportItems[] = "initial-scale=".$mobileViewport["initialScale"];

			if (isset($mobileViewport["maximumScale"]))
				$mobileViewportItems[] = "maximum-scale=".$mobileViewport["maximumScale"];

			$r .= "<meta name=\"viewport\" content=\"".implode(", ", $mobileViewportItems)."\" />\n";
		}

		if ($microsoftApplicationInfo = $this->getConfig("microsoftApplicationInfo")) {
			if ($microsoftApplicationInfo["name"])
				$r .= "<meta name=\"application-name\" content=\"".$microsoftApplicationInfo["name"]."\" />\n";

			if ($microsoftApplicationInfo["tileColor"])
				$r .= "<meta name=\"msapplication-TileColor\" content=\"".$microsoftApplicationInfo["tileColor"]."\" />\n";

			if ($microsoftApplicationInfo["tileImage"])
				$r .= "<meta name=\"msapplication-TileImage\" content=\"".$microsoftApplicationInfo["tileImage"]."\" />\n";
		}

		if ($appleApplicationInfo = $this->getConfig("appleApplicationInfo")) {
			if ($appleApplicationInfo["name"])
				$r .= "<meta name=\"apple-mobile-web-app-title\" content=\"".$appleApplicationInfo["name"]."\" />\n";

			if ($appleApplicationInfo["icons"])
				foreach ($appleApplicationInfo["icons"] as $size => $src)
					$r .= "<link rel=\"apple-touch-icon\" sizes=\"".$size."\" href=\"".$src."\" />\n";
		}

		if ($favIcons = $this->getConfig("favIcons")) {
			if (is_array($favIcons))
				foreach ($favIcons as $size => $src)
					$r .= "<link rel=\"icon\" type=\"image/png\" href=\"".$src."\" sizes=\"".$size."\" />\n";
		}

		$r .= "</head>\n";

		$r .= "<body".(isset($setup["bodyAdditionalCssClasses"]) ? " class=\"".$setup["bodyAdditionalCssClasses"]."\"" : "").">\n";

		return $r;
	}

	/**
	 * footer
	 *
	 * Returns a properly built HTML footer
	 *
	 * @todo Inlined Javascript should be minimized
	 */
	function footer() {
		global $e;

		$r = "";

		if ($this->getConfig("googleAnalyticsTrackingId") && !IS_DEVEL_ENVIRONMENT)
			$r .= $this->getGoogleAnalyticsCode($this->getConfig("googleAnalyticsTrackingId"));

		if ($this->getConfig("matomoTrackingId") && $this->getConfig("matomoServerUrl") && !IS_DEVEL_ENVIRONMENT)
			$r .= $this->getMatomoCode($this->getConfig("matomoServerUrl"), $this->getConfig("matomoTrackingId"));
		
		if ($this->getFooterAdditionalHtml())
			$r .= $this->getFooterAdditionalHtml();

		if ($this->getConfig("isNoticeForOlderInternetExplorer"))
			$r .=
				"<!--[if lt IE 9]>\n".
					"<div style=\"position: fixed; bottom: 0; left: 0; right: 0; background: #fa0; color: #fff; font-size: 13pt; font-weight: bold; padding: 15px; float: left; width: 100%; text-align: center; z-index: 10000;\">You are using a really outdated browser, get <a href=\"http://www.google.com/chrome\">Chrome</a> or <a href=\"http://www.getfirefox.com\">Firefox</a> to enjoy our site (and the rest of the Internet) at its best!</div>\n".
				"<![endif]-->\n";

		// Javascript
		if ($e->Javascript)
			if (is_array($this->javascriptSets)) {
				if($this->getConfig("isDeferJavascript")) {
					$r .=
						"<script type=\"text/javascript\">
							var DOMReady = function(a,b,c){b=document,c='addEventListener';b[c]?b[c]('DOMContentLoaded',a):window.attachEvent('onload',a)}

							DOMReady(function () {
								var element = document.createElement(\"script\");
								element.src = \"".$e->Javascript->getSetUrl($this->javascriptSets)."\";
								document.body.appendChild(element);
							});

							".($this->inlineJavascript ? "
								function executeDeferredInlineJavascript() {
									".$this->inlineJavascript."
								}
							" : null)."
						</script>";
				}
				else
				if ($this->inlineJavascript) {
					$r .=
						"<script type=\"text/javascript\">
							".$this->inlineJavascript."
						</script>";
				}
			}
		
		$r .=
			"</body>\n</html>";

		return $r;
	}


	/**
	 * Returns the HTML code for inserting a Google Analytics analytics code
	 * @param string $trackingId The Google Analytics tracking id to use
	 * @return string The HTML code for Google Analytics tracking for the given $trackingId
	 */
	function getGoogleAnalyticsCode($trackingId) {
		return
			"<script>\n".
				"(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n".
				"(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\n".
				"m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n".
				"})(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n\n".
				"ga('create', '".$trackingId."', 'auto');\n".
				"ga('send', 'pageview');\n\n".
			"</script>";
	}

	/**
	 * Returns the HTML code for inserting a Matomo analytics code
	 * @param string $serverUrl The URL of the Matomo server. Must include a trailing backlash.
	 * @param string $id The Matomo tracking Id
	 * @return string The HTML code for Matomo Analytics tracking
	 */
	function getMatomoCode($serverUrl, $id) {
		return
			"<script>\n".
				"var _paq = _paq || [];\n".
				"_paq.push(['trackPageView']);\n".
				"_paq.push(['enableLinkTracking']);\n".
				"(function() {\n".
					"var u=\"".$serverUrl."\";\n".
					"_paq.push(['setTrackerUrl', u+'piwik.php']);\n".
					"_paq.push(['setSiteId', '".$id."']);\n".
					"var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n".
					"g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);\n".
				"})();\n".
			"</script>";
	}

	/**
	 * responseCode
	 *
	 * Sends a response code to the client, and optionally redirects to the specified location.
	 *
	 * @param integer $code The code, from one of the available HTML_RESPONSE_CODE_NOT_* consts
	 * @param string $location The Url to redirect
	 * @param string $location An optional additional location to redirect the client after the code has been sent
	 */
	function responseCode($code, $location = false) {
		switch ($code) {
			case HTML_RESPONSE_CODE_NOT_FOUND:
				header("HTTP/1.0 404 Not Found");
				break;
			case HTML_RESPONSE_CODE_NO_PERMISSION:
				header("HTTP/1.0 403 Not Found");
				break;
			case HTML_RESPONSE_CODE_INTERNAL_SERVER_ERROR:
				header("HTTP/1.1 500 Internal Server Error");
				break;
			case HTML_RESPONSE_CODE_MOVED_PERMANENTLY:
				header("HTTP/1.1 301 Moved Permanently");
				break;
			case HTML_RESPONSE_CODE_FOUND_REDIRECT:
				header("HTTP/1.1 302 Found");
				break;
		}

		if ($location)
			header ("Location: ".$location);
	}

	/**
	 * Redirects to the given URL via the specified HTTP response code and ends execution
	 *
	 * @param integer $code The code, from one of the available HTML_RESPONSE_CODE_NOT_* consts
	 * @param string $location The Url to redirect
	 * @param string $location A location to redirect the client after the code has been sent
	 */
	function Xredirect($code, $location) {
		global $e;
		$this->responseCode($code, $location);
		$e->end();
	}
}