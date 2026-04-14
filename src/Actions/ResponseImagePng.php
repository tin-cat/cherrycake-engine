<?php

namespace Cherrycake\Actions;

/**
 * Class that represents a response to a client. Mostly used by the Output module.
 */
class ResponseImagePng extends Response {
	/**
	 * @var integer $contentType The content type of the response
	 */
	protected string $contentType = "image/png";
}
