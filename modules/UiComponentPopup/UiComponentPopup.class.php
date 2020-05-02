<?php

/**
 * UiComponentPopup
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentPopup
 *
 * A Ui component to show popups
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentPopup extends \Cherrycake\UiComponent
{
	/**
	 * @var array $dependentCoreModules Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreModules = [
		"UiComponentJquery"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentPopup.css");
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentPopup.js");
	}
}