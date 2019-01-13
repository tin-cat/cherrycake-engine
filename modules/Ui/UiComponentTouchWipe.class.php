<?php

/**
 * UiComponentTouchWipe
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentTouchWipe
 *
 * A Ui component to include the TouchWipe Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentTouchWipe extends UiComponent
{
	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "jquery.touchwipe.1.1.1.js");
	}
}