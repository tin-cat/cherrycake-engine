<?php

/**
 * Engine
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * The main class that loads modules and configurations, and the entry point of the application.
 * Cherrycake uses global variables for configuring modules and global configuration, be sure to set "register_globals" to "off" in php.ini to avoid security issues.
 *
 * @package Cherrycake
 * @category Main
 */
class Engine {
	/**
	 * @var string $appNamespace Holds the namespace for the specific App, it is set in the Init method
	 */
	private $appNamespace;

	/**
	 * @var array $loadedModules Stores the names of all included modules
	 */
	private $loadedModules;

	/**
	 * Initializes the engine
	 *
	 * Setup keys:
	 *
	 * * namespace: Specifies the App namespace
	 * * baseCherrycakeModules: An ordered array of the base Cherrycake module names that has to be always loaded on application start. These must include an "actions" modules that will later determine the action to take based on the received query, thus loading the additional required modules to do so.
	 * * additionalAppConfigFiles: An ordered array of any additional App config files to load that are found under the App config directory
	 *
	 * @param array $setup The initial engine configuration information.
	 * @return boolean Whether all the modules have been loaded ok
	 */
	function init($setup) {
		if (\Cherrycake\isUnderMaintenance()) {
			header("HTTP/1.1 503 Service Temporarily Unavailable");
			echo file_get_contents("errors/maintenance.html");
			die;
		}
		
		date_default_timezone_set(TIMEZONENAME);

		require LIB_DIR."/Module.class.php";

		$this->appNamespace = $setup["namespace"];

		if ($setup["additionalAppConfigFiles"])
			foreach ($setup["additionalAppConfigFiles"] as $additionalAppConfigFile)
				require APP_DIR."/config/".$additionalAppConfigFile;

		if ($setup["baseCherrycakeModules"])
			foreach ($setup["baseCherrycakeModules"] as $module)
				if (!$this->loadCherrycakeModule($module))
					return false;

		return true;
	}

	/**
	 * @return string The namespace used by the App
	 */
	function getAppNamespace() {
		return $this->appNamespace;
	}

	/**
	 * Specific method to load a Cherrycake module. Cherrycake modules are classes extending the module class that provide engine-specific functionalities.
	 *
	 * @param string $moduleName The name of the module to load
	 *
	 * @return boolean Whether the module has been loaded ok
	 */
	function loadCherrycakeModule($moduleName) {
		return $this->loadModule(LIB_DIR."/modules", CONFIG_DIR, $moduleName, __NAMESPACE__);
	}

	/**
	 * Specific method to load an application-specific module. App modules are classes extending the module class that provide app-specific functionalities.
	 *
	 * @param string $moduleName The name of the module to load
	 *
	 * @return boolean Whether the module has been loaded ok
	 */
	function loadAppModule($moduleName) {
		return $this->loadModule(APP_MODULES_DIR, CONFIG_DIR, $moduleName, $this->appNamespace);
	}

	/**
	 * Generic method to load a module. Modules are classes extending the module class providing specific functionalities in a modular-type framework. Module can have its own configuration file.
	 *
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $configDirectory Directory where module configuration files are stored with the syntax [module name].config.php
	 * @param string $moduleName The name of the module to load
	 * @param string $namespace The namespace of the module
	 *
	 * @return boolean Whether the module has been loaded and initted ok
	 */
	function loadModule($modulesDirectory, $configDirectory, $moduleName, $namespace) {
		// Avoids a module to be loaded more than once
		if (is_array($this->loadedModules) && in_array($moduleName, $this->loadedModules))
			return true;

		$this->loadedModules[] = $moduleName;

		$this->includeModuleClass($modulesDirectory, $moduleName);		

		eval("\$this->".$moduleName." = new \\".$namespace."\\Modules\\".$moduleName."();");

		if(!$this->$moduleName->init()) {
			$this->end();
			die;
		}

		return true;
	}

	/*
	 * Generic method to include a module class
	 *
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $moduleName The name of the module whose class must be included
	 */
	function includeModuleClass($modulesDirectory, $moduleName) {
		include_once($modulesDirectory."/".$moduleName."/".$moduleName.".class.php");
	}

	/**
	 * Loads a Cherrycake-specific class. Cherrycake classes are any other classes that are not modules, nor related to any Cherrycake module.
	 *
	 * @param $className The name of the class to load, must be stored in LIB_DIR/[class name].class.php
	 */
	function loadCherrycakeClass($className) {
		include_once(LIB_DIR."/".$className.".class.php");
	}

	/**
	 * Loads a cherrycake-specific class. Cherrycake module classes are any other classes that are not modules, but related to a Cherrycake module.
	 *
	 * @param $moduleName The name of the module to which the class belongs
	 * @param $className The name of the class
	 */
	function loadCherrycakeModuleClass($moduleName, $className) {
		include_once(LIB_DIR."/modules/".$moduleName."/".$className.".class.php");
	}

	/**
	 * Loads an app-specific class. App classes are any other classes that are not directly related to a module.
	 *
	 * @param string $className The name of the class to load, must be stored in APP_CLASSES_DIR/[class name].class.php
	 */
	function loadAppClass($className) {
		include_once(APP_CLASSES_DIR."/".$className.".class.php");
	}

	/**
	 * Loads an app-module specific class. App module classes are classes that do not extend the module class but provide functionalities related to a module.
	 *
	 * @param string $moduleName The name of the module to which the class belongs
	 * @param string $className The name of the class
	 */
	function loadAppModuleClass($moduleName, $className) {
		include_once(APP_MODULES_DIR."/".$moduleName."/".$className.".class.php");
	}

	/**
	 * Attends the request received from a web server by calling Actions::run with the requested URI string
	 */
	function attendWebRequest() {
		$this->Actions->run($_SERVER["REQUEST_URI"]);
	}

	/**
	 * Attends the request received by the PHP cli by calling Actions:run with the first command line argument, which should be a URI
	 */
	function attendCliRequest() {
		global $argv, $argc;

		if (!IS_CLI) {
			header("HTTP/1.1 404");
			return false;
		}

		if ($argc != 2) {
			echo "Cherrycake CLI\nError: One parameter expected, in the form of a URI.\n";
			die;
		}

		$requestUri = $argv[1];

		// If it has get parameters, parse them and put them in $_GET
		if ($firstInterrogantPosition = strpos($requestUri, "?"))
			parse_str(substr($requestUri, $firstInterrogantPosition + 1), $_GET);

		$this->Actions->run($requestUri);
	}

	/**
	 * Ends the application by dumping the Output buffer to the client
	 */
	function end() {
		$this->Output->sendResponse();

		if (is_array($this->loadedModules))
			foreach ($this->loadedModules as $moduleName)
				$this->$moduleName->end();
		
		die;
	}
}

/**
 * Defines an autoloader for requested classes, to allow the automatic inclusion of class files when they're needed. It distinguishes from Cherrycake classes and App classes by checking the namespace
 */
spl_autoload_register(function ($className) {
	$namespace = strstr($className, "\\", true);

	// If autoload for Predis namespace is requested, don't do it. Exception for performance only.
	// This causes the "Predis" namespace name to be forbidden to use when creating a Cherrycake app.
	if ($namespace == "Predis")
		return;

	$fileName = str_replace("\\", "/", substr(strstr($className, "\\"), 1)).".class.php";

	if ($namespace == "Cherrycake")
		include LIB_DIR."/classes/".$fileName;
	else
	if (file_exists(APP_CLASSES_DIR."/".$fileName))
		include APP_CLASSES_DIR."/".$fileName;
});

/**
 * @return bool Whether the app is currently under maintenance or not. Takes also into account the exception IPs
 */
function isUnderMaintenance() {
	global $underMaintenanceExceptionIps;
	return IS_UNDER_MAINTENANCE && !in_array($_SERVER["REMOTE_ADDR"], $underMaintenanceExceptionIps);
}