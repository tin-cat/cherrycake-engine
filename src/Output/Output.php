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
	 * @var \Cherrycake\Actions\Response $response The Response that will be sent to the client
	 */
	private \Cherrycake\Actions\Response|null  $response = null;

	/**
	 * Sets the Response object that will be sent to the client
	 * @param \Cherrycake\Actions\Response $response The response
	 */
	function setResponse(\Cherrycake\Actions\Response $response) {
		$this->response = $response;
	}

	/**
	 * @return \Cherrycake\Actions\Response The current Response object
	 */
	function getResponse(): \Cherrycake\Actions\Response {
		return $this->response;
	}

	/**
	 * Sends the current response. If a response is passed, sets it as the current response and then sends it.
	 * @param \Cherrycake\Actions\Response|null Optionally, the Response to send. If not specified, the current Response will be sent.
	 */
	function sendResponse(
		\Cherrycake\Actions\Response|null $response = null
	) {
		if (!is_null($response))
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
