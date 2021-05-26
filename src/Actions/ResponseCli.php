<?php

namespace Cherrycake\Actions;


/**
 * Class that represents a response to a client via the CLI interface. Mostly used by the Output module.
 *
 * @package Cherrycake
 * @category Classes
 */
class ResponseCli extends Response {
    /**
	 * Sends the response to the client
	 */
	function send() {
		echo $this->getPayloadForClient()."\n";
	}
}
