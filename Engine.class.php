<?php

/**
 * Engine
 *
 * @package Cherrycake
 */

namespace Cherrycake;

const ANSI_NOCOLOR = "\033[0m";
const ANSI_BLACK = "\033[0;30m";
const ANSI_RED = "\033[0;31m";
const ANSI_GREEN = "\033[0;32m";
const ANSI_ORANGE = "\033[0;33m";
const ANSI_BLUE = "\033[0;34m";
const ANSI_PURPLE = "\033[0;35m";
const ANSI_CYAN = "\033[0;36m";
const ANSI_LIGHT_GRAY = "\033[0;37m";
const ANSI_DARK_GRAY = "\033[1;90m";
const ANSI_LIGHT_RED = "\033[35m";
const ANSI_LIGHT_GREEN = "\033[1;32m";
const ANSI_YELLOW = "\033[1;33m";
const ANSI_LIGHT_BLUE = "\033[36m";
const ANSI_LIGHT_PURPLE = "\033[1;35m";
const ANSI_LIGHT_CYAN = "\033[1;36m";
const ANSI_WHITE = "\033[1;37m";

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
	 * @var Cache $cache Holds the bottom-level Cache object
	 */
	private $cache;

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
	 * Builds a cache key based on the passed string or array of strings
	 * 
	 * @param mixed $key A string or an array of strings
	 * @return string The string cache key
	 */
	private function buildCacheKey($key) {
		return "CherrycakeEngine_".APP_NAME."_".is_array($key) ? implode("_", $key) : $key;
	}

	/**
	 * Sets a key into the engine cache.
	 * 
	 * @param mixed $key A string or an array of strings
	 * @param mixed $value The value to store in cache
	 * @param int $ttl The TTL of the item in cache
	 * @return boolean True on success, false on failure
	 */
	private function cacheStore($key, $value, $ttl = 0) {
		if (!apcu_store($this->buildCacheKey($key), serialize($value), $ttl))
			return false;
		$keys = $this->cacheGetKeys();
		if (!$keys || !in_array($key, $keys)) {
			$keys[] = $key;
			return apcu_store("CherrycakeEngine_".APP_NAME."_CachedKeys", serialize($keys), 0);
		}
		return true;
	}

	/**
	 * Retrieves a value from the engine cache
	 * 
	 * @param mixed $key A string or an array of strings
	 * @return mixed The value, null if it didn't exist or false in case of failure
	 */
	private function cacheFetch($key) {
		$value = apcu_fetch($this->buildCacheKey($key));
		if ($value === false)
			return false;
		return $value ? unserialize($value) : null;
	}

	/**
	 * @return mixed An array of all the key names that have been stored in cache for this App, or false on failure.
	 */
	private function cacheGetKeys() {
		$value = apcu_fetch("CherrycakeEngine_".APP_NAME."_CachedKeys");
		if ($value === false)
			return false;
		return $value === false ? false : unserialize($value);
	}

	/**
	 * Clears the entire engine cache
	 */
	function clearCache() {
		$keys = $this->cacheGetKeys();
		if (is_array($keys))
			foreach ($keys as $key)
				apcu_delete($this->buildCacheKey($key));
	}

	/**
	 * @param string $directory The directory on which to search for modules
	 * @return mixed An array of the module names found on the specified directory, or false if none found or the directory couldn't be opened.
	 */
	function getAvailableModuleNamesOnDirectory($directory) {
		$moduleNames = false;
		if (!$handler = opendir($directory))
			return false;
		while (false !== ($file = readdir($handler))) {
			if ($file == "." || $file == "..")
				continue;
			if (is_dir($directory."/".$file))
				$moduleNames[] = $file;
		}
		closedir($handler);
		return $moduleNames;
	}

	/**
	 * @return array All the available Cherrycake module names
	 */
	function getAvailableCherrycakeModuleNames() {
		return $this->getAvailableModuleNamesOnDirectory(LIB_DIR."/modules");
	}

	/**
	 * @return array All the available App module names
	 */
	function getAvailableAppModuleNames() {
		return $this->getAvailableModuleNamesOnDirectory(APP_MODULES_DIR);
	}

	/**
	 * @param string $methodName the name of the method
	 * @return array The Cherrycake module names that implement the specified method
	 */
	function getAvailableCherrycakeModuleNamesWithMethod($methodName) {
		return $this->getAvailableModuleNamesWithMethod("Cherrycake\Modules", LIB_DIR."/modules", $methodName);
	}

	/**
	 * @param string $methodName the name of the method
	 * @return array The App module names that implement the specified method
	 */
	function getAvailableAppModuleNamesWithMethod($methodName) {
		return $this->getAvailableModuleNamesWithMethod("CherrycakeApp\Modules", APP_MODULES_DIR, $methodName);
	}
	/*
	 * @param string $nameSpace The namespace to use
	 * @param string $modulesDirectory The directory where the specified module is stored
	 * @param string $methodName the name of the method to check
	 * @return array The module names that implement the specified method, o,r false if no modules found
	 */
	function getAvailableModuleNamesWithMethod($nameSpace, $modulesDirectory, $methodName) {
		$cacheKey = ["AvailableModuleNamesWithMethod", $nameSpace, $modulesDirectory, $methodName];
		$cacheTtl = IS_DEVEL_ENVIRONMENT ? 3 : 600;

		$modulesWithMethod = $this->cacheFetch($cacheKey);
		if (is_array($modulesWithMethod))
			return $modulesWithMethod;
	
		if (!$moduleNames = $this->getAvailableModuleNamesOnDirectory($modulesDirectory)) {
			$this->cacheStore($cacheKey, [], $cacheTtl);
			return false;
		}

		foreach ($moduleNames as $moduleName) {
			if (!$this->isModuleExists($modulesDirectory, $moduleName)) {
				continue;
			}
			if ($this->isModuleImplementsMethod($nameSpace, $modulesDirectory, $moduleName, $methodName)) {
				$modulesWithMethod[] = $moduleName;
			}
		}

		$this->cacheStore($cacheKey, $modulesWithMethod, $cacheTtl);

		return $modulesWithMethod ?? false;
	}

	/**
	 * @param string $nameSpace The namespace to use
	 * @param string $modulesDirectory The directory where the specified module is stored
	 * @param string $moduleName The name of the module to check
	 * @param string $methodName the name of the method to check
	 * @return boolean True if the specified module implements the specified method
	 */
	function isModuleImplementsMethod($nameSpace, $modulesDirectory, $moduleName, $methodName) {
		$this->includeModuleClass($modulesDirectory, $moduleName);
		return $this->isClassMethodImplemented($nameSpace."\\".$moduleName, $methodName);
	}

	/**
	 * @param string $className The name of the class
	 * @param string $methodname The name of the method
	 * @return boolean True if the method is implemented on the specified class, false if it isn't.
	 */
	function isClassMethodImplemented($className, $methodName) {
		$reflector = new \ReflectionMethod($className, $methodName);
		return $reflector->getDeclaringClass()->getName() == $className;
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

	/**
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $moduleName The name of the module whose class must be included
	 * @return string The file path of the specified module
	 */
	function getModuleFilePath($modulesDirectory, $moduleName) {
		return $modulesDirectory."/".$moduleName."/".$moduleName.".class.php";
	}

	/**
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $moduleName The name of the module whose class must be included
	 * @return boolean Whether the specified module file exists
	 */
	function isModuleExists($modulesDirectory, $moduleName) {
		return file_exists($this->getModuleFilePath($modulesDirectory, $moduleName));
	}

	/*
	 * Generic method to include a module class
	 *
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $moduleName The name of the module whose class must be included
	 */
	function includeModuleClass($modulesDirectory, $moduleName) {
		include_once($this->getModuleFilePath($modulesDirectory, $moduleName));
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
	 * Calls the specified static method on all the available Cherrycake and App modules where it's implemented, and then loads those modules
	 * @param string $methodName The method name to call
	 */
	function callMethodOnAllModules($methodName) {
		// Call the static method
		$cherrycakeModuleNames = $this->getAvailableCherrycakeModuleNamesWithMethod($methodName);
		if (is_array($cherrycakeModuleNames)) {
			foreach ($cherrycakeModuleNames as $cherrycakeModuleName) {
				$this->includeModuleClass(LIB_DIR."/modules", $cherrycakeModuleName);
				forward_static_call(["\\Cherrycake\\Modules\\".$cherrycakeModuleName, $methodName]);
			}
			reset($cherrycakeModuleNames);
		}

		$appModuleNames = $this->getAvailableAppModuleNamesWithMethod($methodName);
		if (is_array($appModuleNames)) {
			foreach ($appModuleNames as $appModuleName) {
				$this->includeModuleClass(\Cherrycake\APP_MODULES_DIR, $appModuleName);
				forward_static_call(["\\".$this->getAppNamespace()."\\Modules\\".$appModuleName, $methodName]);
			}
			reset($appModuleNames);
		}
		
		// Load the modules
		if (is_array($cherrycakeModuleNames)) {
			foreach ($cherrycakeModuleNames as $cherrycakeModuleName) {
				$this->loadCherrycakeModule($cherrycakeModuleName);
			}
		}

		if (is_array($appModuleNames)) {
			foreach ($appModuleNames as $appModuleName) {
				$this->loadAppModule($appModuleName);
			}
		}

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

		if ($argc < 2) {
			$this->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "No action name specified"
			]);
			die;
		}

		$actionName = $argv[1];
		if (!$action = $this->Actions->getAction($actionName)) {
			$this->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Unknown action",
				"errorVariables" => [
					"actionName" => $actionName
				]
			]);
			die;
		}

		// If it has get parameters, parse them and put them in $_GET
		$_GET = $this->parseCommandLineArguments(array_slice($argv, 2));

		if (!$action->request->retrieveParameterValues()) {
			die;
		}

		$action->run();
	}

	/**
	 * Method by mbirth@webwriters.de found at https://www.php.net/manual/en/function.getopt.php#83414
	 * @param array $params The array of parameters to parse, as received by $GLOBALS['argv']. Usually, array_slice($GLOBALS['argv'], 1) will be passed to first remove the first item, which is the executable name
	 * @param array $noopt An array of parameter names that aren't optional
	 * @return array A hash array of each found parameter as the key, and its values
	 */
	function parseCommandLineArguments($params, $noopt = array()) {
        $result = array();
        // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
        reset($params);
		foreach ($params as $tmp => $p) {
            if ($p[0] == '-') {
                $pname = substr($p, 1);
                $value = true;
                if ($pname[0] == '-') {
                    // long-opt (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                }
                // check if next parameter is a descriptor or a value
                $nextparm = current($params);
                if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm[0] != '-') list($tmp, $value) = each($params);
                $result[$pname] = $value;
            } else {
                // param doesn't belong to any option
                $result[] = $p;
            }
        }
        return $result;
    }

	/**
	 * Ends the application
	 */
	function end() {
		if (is_array($this->loadedModules))
			foreach ($this->loadedModules as $moduleName)
				$this->$moduleName->end();
		$this->clearCache();
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

/**
 * A helper function that prints out a variable for debugging purposes
 * @param $var The variable to debug
 */
function debug(&$var) {
	echo "<pre>".print_r($var, true)."</pre>";
}