<?php

namespace Cherrycake\Actions;

/**
 * A class that represents an Action which will return JavaScript
 */
class ActionJavascript extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	protected string $responseClass = "ResponseApplicationJavascript";
}
