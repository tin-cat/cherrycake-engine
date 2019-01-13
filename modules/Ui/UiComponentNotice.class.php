<?php

/**
 * UiComponentNotice
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentNotice
 *
 * A Ui component to show notices, small messages that popup to the user in an unintrusive way, and vanish in time automatically
 *
 * Configuration example for UiComponentnotice.config.php:
 * <code>
 *  $UiComponentAjaxConfig = [
 *      "revealDelay" => 250, // The time in milliseconds for the reveal animation
 *      "hideDelay" => 250, // The time in milliseconds for the hide animation
 *      "isAdvancedEasings" => true, // Whether to use advanced easings or not. Will include "jquery.easing.1.e.js" if set to true, in order to made advanced easings available
 *      "revealEasing" => "easeOutQuint", // The easing name to use for the reveal animation. 'swing' or 'linear' for jQuery defaults, or one of the specified on www.easings.net for the configured easings in jquery.easing.1.3.js
 *      "hideEasing" => "easeInCirc", // The easing name to use for the hide animation. 'swing' or 'linear' for jQuery defaults, or one of the specified on www.easings.net for the configured easings in jquery.easing.1.3.js
 *      "defaultDisappearDelay" => 3000 // The default milliseconds to wait after showing a notice to hide it automatically, if no other specified. Defaults to 3000. Zero for no auto-closing.
 *  ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentNotice extends UiComponent
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Holds the default configuration for this UiComponent
	 */
	protected $config = [
		"revealDelay" => 250, // The time in milliseconds for the reveal animation
		"hideDelay" => 250, // The time in milliseconds for the hide animation
		"isAdvancedEasings" => true, // Whether to use advanced easings or not. Will include "jquery.easing.1.e.js" if set to true, in order to made advanced easings available
		"revealEasing" => "easeOutQuint", // The easing name to use for the reveal animation. 'swing' or 'linear' for jQuery defaults, or one of the specified on www.easings.net for the configured easings in jquery.easing.1.3.js
		"hideEasing" => "easeInCirc", // The easing name to use for the hide animation. 'swing' or 'linear' for jQuery defaults, or one of the specified on www.easings.net for the configured easings in jquery.easing.1.3.js
		"defaultDisappearDelay" => 3000 // The default milliseconds to wait after showing a notice to hide it automatically, if no other specified. Defaults to 3000. Zero for no auto-closing.
	];

	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
		"UiComponentJquery"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentNotice.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentNotice.js");
		if ($this->getConfig("isAdvancedEasings"))
			$e->Javascript->addFileToSet("cherrycakemain", "jquery.easing.1.3.js");
	}
}