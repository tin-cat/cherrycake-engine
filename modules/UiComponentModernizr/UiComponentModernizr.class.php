<?php

/**
 * UiComponentModernizr
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentModernizr
 *
 * A Ui component to include the UiComponentModernizr Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentModernizr extends \Cherrycake\UiComponent {
	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "modernizr.js");
	}
}