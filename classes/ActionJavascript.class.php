<?php

/**
 * ActionJavascript
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A class that represents an Action which will return JavaScript
 *
 * @package Cherrycake
 * @category Classes
 */
class ActionJavascript extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	protected $responseClass = "ResponseApplicationJavascript";
}