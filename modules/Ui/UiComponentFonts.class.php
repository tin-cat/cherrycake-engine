<?php

/**
 * UiComponentFonts
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentFonts
 *
 * A Ui component that adds external font capabilities via Css
 *
 * Configuration example for UiComponentfonts.config.php:
 * <code>
 * $UiComponentFontsConfig = [
 *    "directory" => "res/fonts", // The directory where font files are stored
 *    "fonts" => [
 *        [
 *            "family" => "Open Sans",
 *            "variants" => [
 *                [
 *                    "style" => "light",
 *                    "weight" => "200",
 *                    "baseFileName" => "OpenSans-Light",
 *                    "isEmbedInline" => true // Wheter to embed icon VG files on the CSS itself in Base 64 to avoid multiple HTTP calls or not.
 *                ],
 *                [
 *                    "style" => "regular",
 *                    "weight" => "400",
 *                    "baseFileName" => "OpenSans-Regular",
 *                    "isEmbedInline" => true
 *                ],
 *                [
 *                    "style" => "bold",
 *                    "weight" => "700",
 *                    "baseFileName" => "OpenSans-Bold",
 *                    "isEmbedInline" => true
 *                ]
 *            ]
 *        ]
 *    ]
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFonts extends UiComponent
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	protected $config = [
		"isEmbedFile" => true
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addCssToSet($this->getConfig("cssSetName"), $this->generateCss());
	}

	/**
	 * generateCss
	 *
	 * Generates the Fonts Css
	 *
	 * @return string The Css
	 */
	function generateCss() {
		global $e;

		if (!$fonts = $this->getConfig("fonts"))
			return null;

		$r = "";
		foreach ($fonts as $font)
			foreach ($font["variants"] as $variant) {
				$r .=
					"@font-face {\n".
						"font-family: \"".$font["family"]."\";\n".
						"font-style: ".$variant["style"].";\n".
						"font-weight: ".$variant["weight"].";\n".
						"font-display: ".($variant["display"] ?? false ? $variant["display"] : "block").";\n";

				$r .= "src: ";

				$eotFileName = $this->getConfig("directory")."/".$variant["baseFileName"].".eot";
				if (file_exists($eotFileName))
					$r .=
						"url('".$eotFileName."');\n".
						"src: url('".$eotFileName."?#iefix') format('embedded-opentype'),\n";

				$svgFileName = $this->getConfig("directory")."/".$variant["baseFileName"].".svg";
				if (file_exists($svgFileName))
					$r .=
						(
							$this->getConfig("isEmbedFile")
							?
							"url(".($variant["isEmbedInline"] ?? false ? $e->Css->getFileDataBase64($svgFileName) : $svgFileName).") format('svg'),\n"
							:
							"url('".$svgFileName."') format('svg'),\n"
						);

				$woffFileName = $this->getConfig("directory")."/".$variant["baseFileName"].".woff";
				if (file_exists($woffFileName))
					$r .=
						(
							$this->getConfig("isEmbedFile")
							?
							"url(".($variant["isEmbedInline"] ?? false ? $e->Css->getFileDataBase64($woffFileName) : $woffFileName).") format('woff'),\n"
							:
							"url('".$woffFileName."') format('woff'),\n"
						);

				$ttfFileName = $this->getConfig("directory")."/".$variant["baseFileName"].".ttf";
				if (file_exists($ttfFileName))
					$r .=
						(
							$this->getConfig("isEmbedFile")
							?
							"url(".($variant["isEmbedInline"] ?? false ? $e->Css->getFileDataBase64($ttfFileName) : $ttfFileName).") format('truetype'),\n"
							:
							"url('".$ttfFileName."') format('truetype'),\n"
						);

				$r = substr($r, 0, strlen($r)-strlen(",\n"));

				$r .= ";\n";

				$r .=
					"}\n";
			}

		return $r;
	}
}