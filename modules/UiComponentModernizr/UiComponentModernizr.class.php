<?php

/**
 * UiComponentModernizr
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentModernizr
 *
 * A Ui component to include the UiComponentModernizr Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentModernizr extends \Cherrycake\Module {
	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "modernizr.js");
	}
}