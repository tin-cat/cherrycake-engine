<?php

/**
 * UiComponentTouchWipe
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentTouchWipe
 *
 * A Ui component to include the TouchWipe Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentTouchWipe extends \Cherrycake\UiComponent {
	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "jquery.touchwipe.1.1.1.js");
	}
}