<?php

/**
 * UiComponentSlides
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentSlides
 *
 * A Ui component to create a slides list. Slides are block of content stacked vertically that can span to fullscreen each if wanted, and are designed to act as building blocks that resemble most css frameworks vertical content organization.
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentSlides extends UiComponent {

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentSlides.css");
	}
}