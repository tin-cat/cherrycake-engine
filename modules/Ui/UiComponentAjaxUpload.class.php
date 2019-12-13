<?php

/**
 * UiComponentAjaxUpload
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentAjaxUpload
 *
 * A Ui component to upload single files
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentAjaxUpload extends UiComponent {
	/**
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentAjaxUpload.js");
	}
}