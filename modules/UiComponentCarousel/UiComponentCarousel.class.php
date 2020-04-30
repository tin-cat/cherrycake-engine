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
class UiComponentCarousel extends \Cherrycake\Module
{
	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentCarousel.css");
		$e->Javascript->addFileToSet("cherrycakemain", "jquery-1.11.1.min.js");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentCarousel.js");
		return true;
	}
}