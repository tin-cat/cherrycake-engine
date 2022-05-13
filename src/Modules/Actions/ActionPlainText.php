<?php

namespace Cherrycake\Modules\Actions;

/**
 * A class that represents an Action which will return plain text
 */
class ActionPlainText extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	protected string $responseClass = "ResponseTextPlain";
}
