<?php

namespace Cherrycake\Modules\Actions;

/**
 * Class that represents a response to a client. Mostly used by the Output module.
 */
class ResponseImageGif extends Response {
	/**
	 * @var integer $contentType The content type of the response
	 */
	protected string $contentType = "image/gif";
}