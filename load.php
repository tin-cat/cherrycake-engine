<?php

/**
 * Load
 *
 * Loads cherrycake and prepares the environment to run a Cherrycake App.
 *
 * @package Cherrycake
 */

namespace Cherrycake;

define("ENGINE_DIR", dirname(__FILE__));
define("APP_DIR", substr_compare($realPath = realpath(getcwd()), "/public", -7, 7) ? $realPath : substr($realPath, 0, -7));

require ENGINE_DIR."/ErrorHandler.php";
require ENGINE_DIR."/Engine.class.php";