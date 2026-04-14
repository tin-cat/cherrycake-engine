<?php

namespace Cherrycake\Actions;

/**
 * Class that represents a response to a client. Mostly used by the Output module.
 */
class ResponseApplicationJavascript extends Response {
	/**
	 * @var integer $contentType The content type of the response
	 */
	protected string $contentType = "application/javascript";
}
