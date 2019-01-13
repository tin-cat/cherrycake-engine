<?php

/**
 * UiComponentBigVideo
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentBigVideo
 *
 * A Ui component to include the BigVideo Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentBigVideo extends UiComponent
{
	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
		"UiComponentModernizr",
		"UiComponentJquery",
		"UiComponentVideo"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "bigvideo.js");
	}
}