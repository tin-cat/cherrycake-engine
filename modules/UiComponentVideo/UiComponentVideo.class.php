<?php

/**
 * UiComponentVideo
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentVideo
 *
 * A Ui component to include the Video.js Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentVideo extends \Cherrycake\UiComponent {
	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "video.js");
	}
}