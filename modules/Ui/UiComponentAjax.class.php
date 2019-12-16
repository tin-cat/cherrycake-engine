<?php

/**
 * UiComponentAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentAjax
 *
 * A Ui component that adds Ajax communication functionalities.
 *
 * Configuration example for UiComponentjquery.config.php:
 * <code>
 *  $UiComponentAjaxConfig = [
 *      "defaultRequestType" => "POST", // The default ajax request type to use when no other is specified. Defaults to "POST"
 *      "defaultTimeout" => 10000, // The default request timeout in millieconds to use when no other is specified. Defaults to 10000.
 *      "defaultIsAsync" => true, // Whether the default request must by asynchronous or not if no other value is specified. Defaults to true.
 *      "defaultIsCache" => false, // Whether the default request must be cached or not (regardless of whatever header caches the request returns) if no other value is specified. Defaults to false. (Cache can act if proper caching headers are sent)
 *      "defaultIsCrossDomain" => false, // Whether the default request must allow cross-domain ajax requests or not if no other value is specified. Defaults to false.
 *      "ajaxErrorText" => "Sorry, we had an error!" // The error text to be shown to the user when an ajax error occurs (such as timeout)
 *  ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentAjax extends UiComponent {
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Holds the default configuration for this UiComponent
	 */
	protected $config = [
		"defaultRequestType" => "POST",
		"defaultTimeout" => 10000,
		"defaultIsAsync" => true,
		"defaultIsCache" => false,
		"defaultIsCrossDomain" => false,
		"ajaxErrorText" => "Sorry, we had an error!"
	];

	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
		"UiComponentJquery",
		"UiComponentPopup"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentAjax.js");
	}
}