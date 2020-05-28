<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A class that represents an Action that will be executed via the command line CLI interface
 *
 * @package Cherrycake
 * @category Classes
 */
class ActionCli extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
    protected $responseClass = "ResponseCli";
    
    /**
	 * @var boolean $isCli When set to true, this action will only be able to be executed via the command line CLI interface
	 */
    protected $isCli = true;
}