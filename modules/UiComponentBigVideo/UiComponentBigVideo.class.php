<?php

/**
 * UiComponentBigVideo
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentBigVideo
 *
 * A Ui component to include the BigVideo Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentBigVideo extends \Cherrycake\Module
{
	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
		"UiComponentModernizr",
		"UiComponentJquery",
		"UiComponentVideo"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "bigvideo.js");
		return true;
	}
}