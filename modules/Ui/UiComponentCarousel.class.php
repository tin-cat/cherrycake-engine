<?php

/**
 * UiComponentCarousel
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentCarousel
 *
 * A Ui component to create carousels
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentCarousel extends UiComponent
{
	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentCarousel.css");
		$e->Javascript->addFileToSet("cherrycakemain", "jquery-1.11.1.min.js");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentCarousel.js");
	}
}