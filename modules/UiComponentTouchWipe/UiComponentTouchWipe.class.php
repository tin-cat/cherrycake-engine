<?php

/**
 * UiComponentTouchWipe
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentTouchWipe
 *
 * A Ui component to include the TouchWipe Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentTouchWipe extends \Cherrycake\Module {
	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "jquery.touchwipe.1.1.1.js");
	}
}