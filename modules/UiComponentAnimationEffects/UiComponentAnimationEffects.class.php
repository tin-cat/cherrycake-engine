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
class UiComponentAnimationEffects extends \Cherrycake\UiComponent
{
	/**
	 * @var array $dependentCoreModules Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreModules = [
		"UiComponentJquery"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentAnimationEffects.js");
		return true;
	}
}