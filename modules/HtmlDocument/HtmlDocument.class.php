<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Provides basic tools to build correctly formatted and SEO optimized HTML5 documents
 *
 * @package Cherrycake
 * @category Modules
 * @todo Make html tag's lang parameter match the real language using the Locale module in header method
 * @todo Implement link rel="canonical" based on the Locale module config key "canonicalLocale" (info: https://support.google.com/webmasters/answer/139394?hl=es)
 */
class HtmlDocument  extends \Cherrycake\Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;
	
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"title" => false,  // The page title
		"description" => false,  // The page description
		"copyright" => false, // The page copyright info
		"keywords" => false, // The page keywords
		"languageCode" => "en", // The language code of the page, from the ISO 639-1 standard (https://www.w3schools.com/tags/ref_language_codes.asp)
		"charset" => "utf-8",
		"bodyAdditionalCssClasses" => false,
		"isAllowRobotsIndex" => true, // Whether to allow robots to index the document
		"isAllowRobotsFollow" => true, // Whether to allow robots to follow links on the document
		"isDeferJavascript" => false, // Whether to defer loading of JavaScript or not.
		"mobileViewport" => [ // Configuration for the site when viewed in a mobile device, via the viewport meta
			"width" => "device-width", // The width of the viewport: A number of pixels, or "device-width"
			"userScalable" => true, // Whether or not to let the user pinch to zoom in/out
			"initialScale" => 1, // Optional, the initial scale
			"maximumScale" => 2 // Optional, the maximum scale
		],
		"microsoftApplicationInfo" => [ // Application info for Microsoft standards (i.e: When adding the web as a shortcut in Windows 8)
			"name" => false, // The name of the app
			"tileColor" => false, // The color of the tile on Windows 8, in HTML hexadecimal format (i.e: #dd2153)
			"tileImage" => false, // URL of an image to use as a tile image for Windows 8. Must be in png format
		],
		"iTunesAppId" => false, // The id of a corresponding App in the Apple store.
		"appleApplicationInfo" => [ // Application info for Apple standards (i.e: When adding the web as a shortcut in iOs devices, or to hint the users about the App store APP for this site)
			"name" => false, // The name of the app
			"icons" => false // A hash array of icon sizes where the key is in the [width]x[height] syntax and the value is the icon URL in png format. The standard keys to use here are:57x57 ,114x114 ,72x72 ,144x144 ,60x60 ,120x120 ,76x76 and 152x152.
		],
		"favIcons" => false, // A hash array of icon sizes where the key is in the [width]x[height] syntax and the value is the icon URL in png format. The standard keys to use here are:196x196, 160x160, 96x96, 16x16 and 32x32.
		"matomoServerUrl" => false, // The Matomo (Piwik) server URL, if any.
		"matomoTrackingId" => false, // The Matomo (Piwik) tracking id, if any.
		"googleAnalyticsTrackingId" => false, // The Google Analytics id, if any.
		"cssSets" => false, // An array of the Css set names to link in the HTML document in a single request, or, to add different Css requests instead of one, an array where each item represents a single request, and is an array of Css set names that will be included in each single request. If set to false, all available sets will be linked in a single request. Default: false
		"javascriptSets" => false // An array of the Javascript set names to link in the HTML document in a single request, or, to add different Javascript requests instead of one, an array where each item represents a single request, and is an array of Javascript set names that will be included in each single request. If set to false, all available sets will be linked in a single request. Default: false
	];

	/**
	 * @var string $inlineJavascript Javascript code that must be executed inline from the HTML
	 */
	private $inlineJavascript;

	/**
	 * @var mixed $footerAdditionalHtml HTML code to be additionally added to the end of the document body
	 */
	private $footerAdditionalHtml = false;

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module, to be dumped on the header method
	 */
	var $dependentCoreModules = [
		"Css",
		"Javascript"
	];

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
	 * Builds a standard HTML header, from the <html ... > to the <body ...> tags. It works with the Css and Javascript modules to include the proper CSS/JavaScript calls.
	 *
	 * @param array $setup Setup options to configure the HTML header, with possible keys:
	 * "bodyAdditionalCssClasses" => false // Additional CSS classes for the body element
	 * @return string The HTML header
	 */
	function header($setup = false) {
		global $e;

		$r = "<!DOCTYPE html>\n";

		$r .= "<html lang=\"".$this->getConfig("languageCode")."\">\n";

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
		if ($e->Css) {
			if (!$cssSets = $this->getConfig("cssSets")) {
				$r .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$e->Css->getSetUrl()."\" />\n";
			}
			else
			if (is_array($cssSets[0])) {
				foreach ($cssSets as $cssSet)
					$r .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$e->Css->getSetUrl($cssSet)."\" />\n";
			}
			else
			if (is_array($cssSets))
				$r .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$e->Css->getSetUrl($cssSets)."\" />\n";
		}

		// Javascript
		if ($e->Javascript) {
			if (!$javascriptSets = $this->getConfig("javascriptSets")) {
				$r .= "<script type=\"text/javascript\" src=\"".$e->Javascript->getSetUrl()."\"></script>\n";
			}
			else
			if (is_array($javascriptSets[0])) {
				foreach ($javascriptSets as $javascriptSet)
					$r .= "<script type=\"text/javascript\" src=\"".$e->Javascript->getSetUrl($javascriptSet)."\"></script>\n";
			}
			else
			if (is_array($javascriptSets))
				$r .= "<script type=\"text/javascript\" src=\"".$e->Javascript->getSetUrl($javascriptSets)."\"></script>\n";
		}

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
	 * Builds a standard HTML footer, from the </body> to the </html> tags. Works with the Javascript module to implement deferred JavaScript capabilities.
	 *
	 * @todo Inlined Javascript should be minimized
	 */
	function footer() {
		global $e;

		$r = "";

		if ($this->getConfig("googleAnalyticsTrackingId") && !$e->isDevel())
			$r .= $this->getGoogleAnalyticsCode($this->getConfig("googleAnalyticsTrackingId"));

		if ($this->getConfig("matomoTrackingId") && $this->getConfig("matomoServerUrl") && !$e->isDevel())
			$r .= $this->getMatomoCode($this->getConfig("matomoServerUrl"), $this->getConfig("matomoTrackingId"));
		
		if ($this->getFooterAdditionalHtml())
			$r .= $this->getFooterAdditionalHtml();

		// Javascript
		if ($e->Javascript) {

			if ($this->getConfig("isDeferJavascript") && $javascriptSets = $this->getConfig("javascriptSets")) {
				if (is_array($javascriptSets[0])) {
					foreach ($javascriptSets as $javascriptSet) {
						$r .=
							"<script type=\"text/javascript\">
								var DOMReady = function(a,b,c){b=document,c='addEventListener';b[c]?b[c]('DOMContentLoaded',a):window.attachEvent('onload',a)}
								DOMReady(function () {
									var element = document.createElement(\"script\");
									element.src = \"".$e->Javascript->getSetUrl($javascriptSet)."\";
									document.body.appendChild(element);
								});
							</script>";
					}
				}
				else {
					$r .=
						"<script type=\"text/javascript\">
							var DOMReady = function(a,b,c){b=document,c='addEventListener';b[c]?b[c]('DOMContentLoaded',a):window.attachEvent('onload',a)}
							DOMReady(function () {
								var element = document.createElement(\"script\");
								element.src = \"".$e->Javascript->getSetUrl($javascriptSets)."\";
								document.body.appendChild(element);
							});
						</script>";
				}
			}

			if ($this->inlineJavascript) {
				if ($this->getConfig("isDeferJavascript")) {
					$r .=
						"<script type=\"text/javascript\">
							function executeDeferredInlineJavascript() {
								".$this->inlineJavascript."
							}
						</script>";
				}
				else {					
					$r .=
						"<script type=\"text/javascript\">
							".$this->inlineJavascript."
						</script>";
				}
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
}