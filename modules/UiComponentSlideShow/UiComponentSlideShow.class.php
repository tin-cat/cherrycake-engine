<?php

/**
 * UiComponentSlideShow
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentSlideShow
 *
 * A Ui component to create slideshows
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentSlideShow extends \Cherrycake\Module {
	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
		"UiComponentJquery",
		"UiComponentTouchWipe"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentSlideShow.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentSlideShow.js");
	}
}