<?php

/**
 * UiComponentPopup
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentPopup
 *
 * A Ui component to show popups
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentPopup extends \Cherrycake\Module
{
	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
		"UiComponentJquery"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentPopup.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentPopup.js");
	}
}