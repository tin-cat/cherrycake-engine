<?php

namespace Cherrycake\Actions;

use Cherrycake\Engine;

/**
 * A class that represents an Ajax JSON response, intended to be handled by the Javascript part of the Ajax module
 */
class AjaxResponseJson {

	const SUCCESS = 0;
	const ERROR = 1;

	/**
	 * @var integer $code The response code, one of the available SUCCESS or ERROR consts
	 */
	protected $code;

	/**
	 * @var string $description The succes description, if any
	 */
	protected $description;

	/**
	 * @var string $redirectUrl The URL to automatically redirect the client to when this response is received by it. Leave to false if no redirection should be done.
	 */
	protected $redirectUrl;

	/**
	 * @var array Arbitraty data to include on the response
	 */
	protected $data;

	/**
	 * @param string $setup The configuration for the Ajax response
	 */
	function __construct($setup) {
		if (isset($setup["code"]))
			$this->code = $setup["code"]; // The response code, one of * available consts
		if (isset($setup["description"]))
			$this->description = $setup["description"]; // The description of the response code, whether successed or failed. Leave to empty if no notification should be shown.
		if (isset($setup["redirectUrl"]))
			$this->redirectUrl = $setup["redirectUrl"]; // The URL to automatically redirect the client to when this response is received by it. Leave to false if no redirection should be done.
		if (isset($setup["data"]))
			$this->data = $setup["data"]; // Any additional data to be included on the response. It is used by other classes extended from this in order to provide specific functionalities on how to display results, like AjaxPopupResponseJson or AjaxNoticeResponseJson
	}

	/**
	 * @return Response The response
	 */
	function getResponse() {

		$r["code"] = $this->code;

		if ($this->description) {
			$r["description"] = $this->description;
		}

		if ($this->redirectUrl)
			$r["redirectUrl"] = $this->redirectUrl;

		if ($this->data)
			$r["data"] = $this->data;

		return new \Cherrycake\Actions\ResponseApplicationJson(payload: $r);
	}

	/**
	 * output
	 *
	 * Outputs the ajax response
	 */
	function output() {
		Engine::e()->Output->setResponse($this->getResponse());
	}
}
