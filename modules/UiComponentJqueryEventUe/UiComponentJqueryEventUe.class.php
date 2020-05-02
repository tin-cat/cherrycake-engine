<?php

/**
 * UiComponentJqueryEventUe
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentJqueryEventUe
 *
 * A Ui component to include the jquery.event.ue plugin (https://github.com/mmikowski/jquery.event.ue)
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentJqueryEventUe extends \Cherrycake\UiComponent
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * @var array $dependentCoreModules Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreModules = [
        "UiComponentJquery"
    ];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "jquery.event.ue.min.js");
	}
}