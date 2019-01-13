<?php

/**
 * Output
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const RESPONSE_OK = 200;
const RESPONSE_NOT_FOUND = 404;
const RESPONSE_NO_PERMISSION = 403;
const RESPONSE_INTERNAL_SERVER_ERROR = 500;
const RESPONSE_REDIRECT_MOVED_PERMANENTLY = 301;
const RESPONSE_REDIRECT_FOUND = 302;

/**
 * Output
 *
 * Manages the final output produced by the App.
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 *
 * @package Cherrycake
 * @category Modules
 */
class Output extends \Cherrycake\Module {
	/**
	 * @var Response $response The Response that will be sent to the client
	 */
	private $response = null;

	/**
	 * Initializes the module
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		return parent::init();
	}

	/**
	 * Sets the Response object that will be sent to the client
	 */
	function setResponse($response) {
		$this->response = $response;
	}

	/**
	 * @return Response The current Response object
	 */
	function getResponse() {
		return $this->response;
	}

	/**
	 * Sends the current response. If a response is passed, sets it as the current response and then sends it.
	 * @param Response Optionally, the Response to send. If not specified, the current Response will be sent.
	 */
	function sendResponse($response = false) {
		if ($response)
			$this->setResponse($response);
		if ($this->response)
			$this->response->send();
	}
}