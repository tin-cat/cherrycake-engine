<?php

/**
 * UiComponentModernizr
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentModernizr
 *
 * A Ui component to include the UiComponentModernizr Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentModernizr extends UiComponent
{
	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "modernizr.js");
	}
}