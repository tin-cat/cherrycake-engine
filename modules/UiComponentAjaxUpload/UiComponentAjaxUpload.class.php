<?php

/**
 * UiComponentAjaxUpload
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentAjaxUpload
 *
 * A Ui component to upload single files
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentAjaxUpload extends \Cherrycake\UiComponent {
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;
	
	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentAjaxUpload.js");
		return true;
	}
}