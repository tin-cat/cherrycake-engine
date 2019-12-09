<?php

/**
 * ActionAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A class that represents an Action which will return Html
 *
 * @package Cherrycake
 * @category Classes
 */
class ActionAjax extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	private $responseClass = "ResponseApplicationJson";
}