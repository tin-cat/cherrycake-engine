<?php

/**
 * UiComponentVideo
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentVideo
 *
 * A Ui component to include the Video.js Javascript library
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentVideo extends \Cherrycake\Module {
	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "video.js");
	}
}