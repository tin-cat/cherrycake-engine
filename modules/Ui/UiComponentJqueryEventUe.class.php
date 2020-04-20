<?php

/**
 * UiComponentJqueryEventUe
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentJqueryEventUe
 *
 * A Ui component to include the jquery.event.ue plugin (https://github.com/mmikowski/jquery.event.ue)
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentJqueryEventUe extends UiComponent
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
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
		$e->Javascript->addFileToSet("cherrycakemain", "jquery.event.ue.min.js");
	}
}