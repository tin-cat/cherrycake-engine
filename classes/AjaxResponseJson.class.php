<?php

/**
 * AjaxResponseJson
 *
 * @package Cherrycake
 */

namespace Cherrycake;

const AJAXRESPONSEJSON_SUCCESS = 0;
const AJAXRESPONSEJSON_ERROR = 1;

const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_NONE = 0;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_NOTICE = 1;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP = 2;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL = 3;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_CONSOLE = 4;

/**
 * AjaxResponseJson
 *
 * A class that represents an Ajax JSON response, intended to be handled by the Javascript part of the Ajax module
 *
 * @package Cherrycake
 * @category Classes
 */
class AjaxResponseJson {
	/**
	 * @var integer $code The response code, one of the available AJAXRESPONSEJSON_* consts
	 */
	protected $code;

	/**
	 * @var string $description The succes description, if any
	 */
	protected $description;

	/**
	 * @var integer $messageType The type of the message to show to the user, when there is one. One of the available AJAXRESPONSEJSON_UI_MESSAGE_TYPE_* consts
	 */
	protected $messageType = AJAXRESPONSEJSON_UI_MESSAGE_TYPE_NONE;

	/**
	 * @var string $redirectUrl The URL to automatically redirect the client to when this response is received by it. Leave to false if no redirection should be done.
	 */
	protected $redirectUrl;

	/**
	 * @var array Arbitraty data to include on the response
	 */
	protected $data;

	/**
	 * AjaxResponse
	 *
	 * Constructor factory
	 *
	 * @param string $setup The configuration for the Ajax response
	 */
	function __construct($setup) {
		$this->code = $setup["code"]; // The response code, one of AJAXRESPONSEJSON_* available consts
		$this->description = $setup["description"]; // The description of the response code, whether successed or failed. Leave to empty if no notification should be shown.
		$this->messageType = $setup["messageType"]; // The message type to be shown when a description is specified, one of const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_* available consts.
		$this->redirectUrl = $setup["redirectUrl"]; // The URL to automatically redirect the client to when this response is received by it. Leave to false if no redirection should be done.
		$this->data = $setup["data"]; // Any additional data to be included on the response. It is used by other classes extended from this in order to provide specific functionalities on how to display results, like AjaxPopupResponseJson or AjaxNoticeResponseJson
	}

	/**
	 * output
	 *
	 * Outputs the ajax response
	 */
	function output() {
		global $e;

		$r["code"] = $this->code;

		if ($this->description) {
			$r["description"] = $this->description;
			$r["messageType"] = $this->messageType;
		}

		if ($this->redirectUrl)
			$r["redirectUrl"] = $this->redirectUrl;

		if ($this->data)
			$r["data"] = $this->data;

		$e->Output->setResponse(new \Cherrycake\ResponseApplicationJson([
			"payload" => $r
		]));
	}
}