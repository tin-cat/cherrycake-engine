<?php

namespace Cherrycake\Modules\Actions;

/**
 * Class that represents a response to a client. Mostly used by the Output module.
 */
class ResponseTextHtml extends Response {
	/**
	 * @var string $contentType The content type of the response
	 */
	protected string $contentType = "text/html";
}
