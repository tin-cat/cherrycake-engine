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
class UiComponentBigVideo extends \Cherrycake\UiComponent
{
	/**
	 * @var array $dependentCoreModules Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreModules = [
		"UiComponentModernizr",
		"UiComponentJquery",
		"UiComponentVideo"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "bigvideo.js");
		return true;
	}
}