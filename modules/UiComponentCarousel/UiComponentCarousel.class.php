<?php

/**
 * UiComponentCarousel
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentCarousel
 *
 * A Ui component to create carousels
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentCarousel extends \Cherrycake\UiComponent
{
	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentCarousel.css");
		$e->Javascript->addFileToSet("coreUiComponents", "jquery-1.11.1.min.js");
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentCarousel.js");
		return true;
	}
}