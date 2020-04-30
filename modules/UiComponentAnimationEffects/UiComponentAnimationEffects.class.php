<?php

/**
 * UiComponentAnimationEffects
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentAnimationEffects
 *
 * A Ui component that adds animation effects
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentAnimationEffects extends \Cherrycake\Module
{
	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
		"UiComponentJquery"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentAnimationEffects.js");
		return true;
	}
}