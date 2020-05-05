<?php

/**
 * UiComponentGrid
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 *
 * A Ui component to generate CSS grids
 *
 * Configuration example for UiComponentgrid.config.php:
 * <code>
 *  $UiComponentGridConfig = [
 *      "responsiveBreakpoints" => [ // The width breakpoints at which grid bricks will start collapsing to fit the screen
 *		"big" => 1280,
 *		"medium" => 980,
 *		"small" => 500
 *	]
 *  ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentGrid extends \Cherrycake\UiComponent {

	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"responsiveBreakpoints" => [
			"big" => 1280,
			"medium" => 980,
			"small" => 700
		]
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentGrid.css");
	}

}