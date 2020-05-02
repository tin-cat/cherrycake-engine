<?php

/**
 * Response
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Response
 *
 * Class that represents a response to a client. Mostly used by the Output module.
 *
 * @package Cherrycake
 * @category Classes
 */
class Response {
	/**
	 * @var integer $contentType The content type of the response
	 */
	protected $contentType;

	/**
	 * @var array $headers Holds the headers to send to the client
	 */
	private $headers = false;

	/**
	 * @var integet $code The response code, one of the RESPONSE_* availables
	 */
	private $code = \Cherrycake\RESPONSE_OK;

	/**
	 * @var string $url The url to redirect
	 */
	private $url = false;

	/**
	 * @var string $payload The response payload. HTML code, for example.
	 */
	private $payload = null;

	function __construct($setup = false) {
		if (isset($setup["code"]))
			$this->setCode($setup["code"]);

		if (isset($setup["url"]))
			$this->setUrl($setup["url"]);

		if (isset($setup["payload"]))
			$this->setPayload($setup["payload"]);
	}

	/**
	 * Adds a header to be sent to the client
	 * @param string $header The header
	 */
	function addHeader($header) {
		$this->headers[] = $header;
	}

	/**
	 * Sets the code
	 * @param integet $code The code, one of the available RESPONSE_*
	 */
	function setCode($code) {
		$this->code = $code;
	}

	/**
	 * Sets the url
	 * @param string $url The url to redirect to
	 */
	function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * Sets the payload
	 * @param string $payload The payload
	 */
	function setPayload($payload) {
		$this->payload = $payload;
	}

	/**
	 * Appends the given payload
	 * @param string $payload The payload to append
	 */
	function appendPayload($payload) {
		$this->payload .= $payload;
	}

	/**
	 * Prepends the given payload
	 * @param string $payload The payload to prepend
	 */
	function prependPayload($payload) {
		$this->payload .= $payload.$this->payload;
	}

	/**
	 * Empties the payload
	 */
	function emptyPayload() {
		$this->payload = null;
	}

	/**
	 * @return string The Payload
	 */
	function getPayload() {
		return $this->payload;
	}

	/**
	 * This method is intended to be overloaded if other types of Responses need to treat the payload in some way before sending it to the client. For example, generating a JSON string from the variable stored as payload.
	 * @return string The Payload as the client expects it
	 */
	function getPayloadForClient() {
		return $this->getPayload();
	}

	/**
	 * @return string The content type mime type string
	 */
	function getContentType() {
		return $this->contentType;
	}

	/**
	 * Sends the response to the client
	 */
	function send() {
		$this->addResponseHeader();
		if ($this->getContentType())
			$this->addHeader("Content-type: ".$this->getContentType());
		if ($this->url)
			$this->addHeader("Location: ".$this->url);
		$this->sendHeaders();
		echo $this->getPayloadForClient();
	}

	function addResponseHeader() {
		switch ($this->code) {
			case \Cherrycake\RESPONSE_OK:
				$this->addHeader("HTTP/1.0 200 Ok");
				break;
			case \Cherrycake\RESPONSE_NOT_FOUND:
				$this->addHeader("HTTP/1.0 404 Not Found");
				break;
			case \Cherrycake\RESPONSE_NO_PERMISSION:
				$this->addHeader("HTTP/1.0 403 Not Found");
				break;
			case \Cherrycake\RESPONSE_INTERNAL_SERVER_ERROR:
				$this->addHeader("HTTP/1.1 500 Internal Server Error");
				break;
			case \Cherrycake\RESPONSE_REDIRECT_MOVED_PERMANENTLY:
				$this->addHeader("HTTP/1.1 301 Moved Permanently");
				break;
			case \Cherrycake\RESPONSE_REDIRECT_FOUND:
				$this->addHeader("HTTP/1.1 302 Found");
				break;
		}
	}

	/**
	 * Sends the headers to the client
	 */
	function sendHeaders() {
		if ($this->headers)
			array_walk($this->headers, function($header) {
				header($header);
			});
	}
}