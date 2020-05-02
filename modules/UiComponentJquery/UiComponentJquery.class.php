<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to include the jQuery Javascript library.
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentJquery extends \Cherrycake\UiComponent {
	protected $isConfigFile = true;
	protected $config = [
		"version" => "3.4.1",
		"isMinified" => true
	];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "jquery-".$this->getConfig("version").($this->getConfig("isMinified") ? ".min" : "").".js");
	}
}