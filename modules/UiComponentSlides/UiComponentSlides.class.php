<?php

/**
 * UiComponentSlides
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentSlides
 *
 * A Ui component to create a slides list. Slides are block of content stacked vertically that can span to fullscreen each if wanted, and are designed to act as building blocks that resemble most css frameworks vertical content organization.
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentSlides extends \Cherrycake\UiComponent {
	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentSlides.css");
	}
}