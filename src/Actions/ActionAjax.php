<?php

namespace Cherrycake\Actions;

/**
 * A class that represents an Action which will return Json for an Ajax request
 */
class ActionAjax extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	protected string $responseClass = "ResponseApplicationJson";
}
