<?php

/**
 * UiComponentSlideShow
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentSlideShow
 *
 * A Ui component to create slideshows
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentSlideShow extends UiComponent
{
	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
		"UiComponentJquery",
		"UiComponentTouchWipe"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentSlideShow.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentSlideShow.js");
	}
}