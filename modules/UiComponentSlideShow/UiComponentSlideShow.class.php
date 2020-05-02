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
class UiComponentSlideShow extends \Cherrycake\UiComponent {
	/**
	 * @var array $dependentCoreModules Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreModules = [
		"UiComponentJquery",
		"UiComponentTouchWipe"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentSlideShow.css");
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentSlideShow.js");
	}
}