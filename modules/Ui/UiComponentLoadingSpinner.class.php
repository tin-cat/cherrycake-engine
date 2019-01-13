<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to show purely-CSS animated loading spinners
 *
 * Configuration example for UiComponenticons.config.php:
 * <code>
 *  $UiComponentLoadingSpinnerConfig = [
 *      "defaultStyle" => "Heartbeat", // The default spinner style to use when none specified.
 *  );
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentLoadingSpinner extends UiComponent {
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Holds the default configuration for this UiComponent
	 */
	protected $config = [
		"defaultStyle" => "Heartbeat", // The default spinner style to use when none specified.
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentLoadingSpinner.css");
	}
}