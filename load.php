<?php

/**
 * Load
 *
 * Loads cherrycake and prepares the environment to run a Cherrycake App.
 *
 * @package Cherrycake
 */

namespace Cherrycake;

define("LIB_DIR", dirname(__FILE__));
define("APP_DIR", substr_compare($realPath = realpath(getcwd()), "/public", -7, 7) ? $realPath : substr($realPath, 0, -7));

require LIB_DIR."/config/Cherrycake.config.php";
require APP_DIR."/config/Cherrycake.config.php";

require LIB_DIR."/ErrorHandler.php";
require LIB_DIR."/Engine.class.php";