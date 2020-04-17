<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class that represents a list of SystemLogEvent objects
 *
 * @package Cherrycake
 * @category Classes
 */
class SystemLogEvents extends \Cherrycake\Items {
    protected $tableName = "cherrycake_systemLog";
    protected $itemClassName = "\Cherrycake\SystemLogEvent";
}