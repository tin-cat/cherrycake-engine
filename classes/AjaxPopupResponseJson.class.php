<?php

/**
 * AjaxPopupResponseJson
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * AjaxPopupResponseJson
 *
 * A class that represents an Ajax JSON response suited for the UiComponentPopup Ui component when opening windows containing results from ajax queries, intended to be handled by the Javascript part of the Ajax module
 *
 * @package Cherrycake
 * @category Classes
 */
class AjaxPopupResponseJson extends AjaxResponseJson {
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
		$this->data["popupContent"] = $setup["popupContent"];
		$this->data["popupStyle"] = $setup["popupStyle"];
		$this->data["popupWidth"] = $setup["popupWidth"];
		$this->data["popupHeight"] = $setup["popupHeight"];
		$this->data["isAutoClose"] = $setup["isAutoClose"];
		$this->data["autoCloseDelay"] = $setup["autoCloseDelay"];
	}
}