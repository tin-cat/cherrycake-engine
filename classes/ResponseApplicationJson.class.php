<?php

/**
 * ResponseApplicationJson
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
class ResponseApplicationJson extends Response {
	/**
	 * @var integer $contentType The content type of the response
	 */
	protected $contentType = "application/json";

	/**
	 * This method is intended to be overloaded if other types of Responses need to treat the payload in some way before sending it to the client. For example, generating a JSON string from the variable stored as payload.
	 * @return string The Payload as the client expects it
	 */
	function getPayloadForClient() {
		return json_encode($this->getPayload());
	}
}