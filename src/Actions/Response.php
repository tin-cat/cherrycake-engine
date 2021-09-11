<?php

namespace Cherrycake\Actions;

/**
 * Class that represents a response to a client. Mostly used by the Output module.
 */
class Response {
	/**
	 * @var string $contentType The content type of the response
	 */
	protected string $contentType = '';

	/**
	 * @var array $headers Holds the headers to send to the client
	 */
	private array $headers = [];

	function __construct(
		private int|null $code = \Cherrycake\Output\RESPONSE_OK,
		private string $url = '',
		private string|array $payload = '',
	) {}

	/**
	 * Adds a header to be sent to the client
	 * @param string $header The header
	 */
	function addHeader(string $header) {
		$this->headers[] = $header;
	}

	/**
	 * Sets the code
	 * @param int $code The code, one of the available RESPONSE_*
	 */
	function setCode(int $code) {
		$this->code = $code;
	}

	/**
	 * Sets the url
	 * @param string $url The url to redirect to
	 */
	function setUrl(string $url) {
		$this->url = $url;
	}

	/**
	 * Sets the payload
	 * @param string $payload The payload
	 */
	function setPayload(string $payload) {
		$this->payload = $payload;
	}

	/**
	 * Appends the given payload
	 * @param string $payload The payload to append
	 */
	function appendPayload(string $payload) {
		$this->payload .= $payload;
	}

	/**
	 * Prepends the given payload
	 * @param string $payload The payload to prepend
	 */
	function prependPayload(string $payload) {
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
	function getPayloadForClient(): string {
		return $this->getPayload();
	}

	/**
	 * @return string The content type mime type string
	 */
	function getContentType(): string {
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
			case \Cherrycake\Output\RESPONSE_OK:
				$this->addHeader("HTTP/1.0 200 Ok");
				break;
			case \Cherrycake\Output\RESPONSE_NOT_FOUND:
				$this->addHeader("HTTP/1.0 404 Not Found");
				break;
			case \Cherrycake\Output\RESPONSE_NO_PERMISSION:
				$this->addHeader("HTTP/1.0 403 Not Found");
				break;
			case \Cherrycake\Output\RESPONSE_INTERNAL_SERVER_ERROR:
				$this->addHeader("HTTP/1.1 500 Internal Server Error");
				break;
			case \Cherrycake\Output\RESPONSE_REDIRECT_MOVED_PERMANENTLY:
				$this->addHeader("HTTP/1.1 301 Moved Permanently");
				break;
			case \Cherrycake\Output\RESPONSE_REDIRECT_FOUND:
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
