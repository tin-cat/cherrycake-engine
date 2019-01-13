<?php

/**
 * UiComponentVideo
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentVideo
 *
 * A Ui component to include the Video.js Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentVideo extends UiComponent
{
	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "video.js");
	}
}