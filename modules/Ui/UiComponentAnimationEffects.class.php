<?php

/**
 * UiComponentAnimationEffects
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentAnimationEffects
 *
 * A Ui component that adds animation effects
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentAnimationEffects extends UiComponent
{
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
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentAnimationEffects.js");
	}
}