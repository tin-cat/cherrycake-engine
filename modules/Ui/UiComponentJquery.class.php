<?php

/**
 * UiComponentJquery
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentJquery
 *
 * A Ui component to include the jQuery Javascript library.
 *
 * Configuration example for UiComponentjquery.config.php:
 * <code>
 *  $UiComponentJQueryConfig = [
 *      "version" => "1.11.1", // The jQuery version to use. Defaulted to 1.11.1
 *      "isMinified" => true // Whether to use the minified version or not. Defaulted to true
 *  ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentJquery extends UiComponent
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Holds the default configuration for this UiComponent
	 */
	protected $config = [
		"version" => "3.3.1",
		"isMinified" => true
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "jquery-".$this->getConfig("version").($this->getConfig("isMinified") ? ".min" : "").".js");
	}
}