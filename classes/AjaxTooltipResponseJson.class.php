<?php

/**
 * AjaxTooltipResponseJson
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * AjaxTooltipResponseJson
 *
 * A class that represents an Ajax JSON response suited for the UiComponentTooltip Ui component when showing tooltips containing results from ajax queries, intended to be handled by the Javascript part of the Ajax module
 *
 * @package Cherrycake
 * @category Classes
 */
class AjaxTooltipResponseJson extends AjaxResponseJson {
	/**
	 * AjaxResponse
	 *
	 * Constructor factory
	 *
	 * @param string $setup The configuration for the Ajax response
	 */
	function __construct($setup) {
		$this->code = $setup["code"];
		$this->description = $setup["description"];
		$this->messageType = $setup["messageType"];
		$this->data["tooltipContent"] = $setup["tooltipContent"];
		$this->data["tooltipStyle"] = $setup["tooltipStyle"];
	}
}