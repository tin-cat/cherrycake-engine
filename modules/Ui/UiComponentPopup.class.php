<?php

/**
 * UiComponentPopup
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentPopup
 *
 * A Ui component to show popups
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentPopup extends UiComponent
{
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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentPopup.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentPopup.js");
	}
}