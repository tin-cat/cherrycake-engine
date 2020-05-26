<?php

/**
 * ActionPlainText
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A class that represents an Action which will return plain text
 *
 * @package Cherrycake
 * @category Classes
 */
class ActionPlainText extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	protected $responseClass = "ResponseTextPlain";
}