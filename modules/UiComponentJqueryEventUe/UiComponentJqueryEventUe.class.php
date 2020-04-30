<?php

/**
 * UiComponentJqueryEventUe
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentJqueryEventUe
 *
 * A Ui component to include the jquery.event.ue plugin (https://github.com/mmikowski/jquery.event.ue)
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentJqueryEventUe extends \Cherrycake\Module
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
        "UiComponentJquery"
    ];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("cherrycakemain", "jquery.event.ue.min.js");
	}
}