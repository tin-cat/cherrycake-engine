<?php

namespace Cherrycake\Modules\Actions;


/**
 * Class that represents a response to a client via the CLI interface. Mostly used by the Output module.
 */
class ResponseCli extends Response {
    /**
	 * Sends the response to the client
	 */
	function send() {
		echo $this->getPayloadForClient()."\n";
	}
}
