<?php

namespace Cherrycake\Output;

/**
 * Output
 *
 * Manages the final output produced by the app.
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 *
 * @package Cherrycake
 * @category Modules
 */
class Output  extends \Cherrycake\Module {
	/**
	 * @var Response $response The Response that will be sent to the client
	 */
	private $response = null;

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

	/**
	 * Performs any tasks needed to end this module.
	 * Called when the engine ends.
	 */
	function end() {
		$this->sendResponse();
	}
}
