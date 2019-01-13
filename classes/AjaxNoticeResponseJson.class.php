<?php

/**
 * AjaxNoticeResponseJson
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * AjaxNoticeResponseJson
 *
 * A class that represents an Ajax JSON response suited for the UiComponentNotice Ui component when showing notices containing results from ajax queries, intended to be handled by the Javascript part of the Ajax module
 *
 * @package Cherrycake
 * @category Classes
 */
class AjaxNoticeResponseJson extends AjaxResponseJson {
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
		$this->data["noticeContent"] = $setup["noticeContent"];
		$this->data["noticeStyle"] = $setup["noticeStyle"];
		$this->data["disappearDelay"] = $setup["disappearDelay"];
	}
}