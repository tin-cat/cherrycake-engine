<?php

/**
 * Engine
 *
 * @package Cherrycake
 */

namespace Cherrycake {

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

	const MODULE_LOADING_ORIGIN_MANUAL = 0;
	const MODULE_LOADING_ORIGIN_BASE = 1;
	const MODULE_LOADING_ORIGIN_DEPENDENCY = 2;
	const MODULE_LOADING_ORIGIN_AUTOLOAD = 3;
	const MODULE_LOADING_ORIGIN_GETTER = 4;

	/**
	 * The main class that loads modules and configurations, and the entry point of the application.
	 * Cherrycake uses global variables for configuring modules and global configuration, be sure to set "register_globals" to "off" in php.ini to avoid security issues.
	 *
	 * @package Cherrycake
	 * @category Main
	 */
	class Engine {
		/**
		 * @var string $appNamespace The namespace of the App
		 */
		private $appNamespace;

		/**
		 * @var string $appName The name of the App
		 */
		private $appName = "CherrycakeApp";

		/**
		 * @var bool $isDevel Whether the App is in development environment or not
		 */
		private $isDevel = false;

		/**
		 * @var bool $isUnderMaintenance Whether the App is under maintenance or not
		 */
		private $isUnderMaintenance = false;

		/**
		 * @var bool $isCli Whether the engine is running as cli or not
		 */
		private $isCli = null;

		/**
		 * @var array $underMaintenanceExceptionIps An array of IPs that will be considered as exceptions to the "under maintenance" mode when connecting
		 */
		private $underMaintenanceExceptionIps = [];

		/**
		 * @var string $configDir The App directory where configuration files reside
		 */
		private $configDir = "config";

		/**
		 * @var string $appModulesDir The App directory where app modules reside
		 */
		private $appModulesDir = "modules";

		/**
		 * @var string $appClassesDir The App directory where app classes reside
		 */
		private $appClassesDir = "classes";

		/**
		 * @var string $timeZone The system's timezone. All modules, including Database for date/time retrievals/saves will be made taking this timezone into account. The server is expected to run on this timezone. Standard "Etc/UTC" is recommended.
		 */
		private $timezoneName = "Etc/UTC";

		/**
		 * @var int $timezoneId The system's timezone. The same as timezoneName, but the matching id on the cherrycake timezones database table
		 */
		private $timezoneId = "532";

		/**
		 * @var Cache $cache Holds the bottom-level Cache object
		 */
		public $cache;

		/**
		 * @var array $loadedModules Stores the names of all included modules
		 */
		private $loadedModules;

		/**
		 * @var array $moduleLoadingHistory Stores a history of the loaded modules.
		 */
		private $moduleLoadingHistory;

		/**
		 * @var int $executionStartHrTime The system's high resolution time where the execution started
		 */
		private $executionStartHrTime;

		static function preInit() {

		}

		/**
		 * Initializes the engine
		 *
		 * @param string $appNamespace The namespace of the app.
		 * @param array $setup The initial engine configuration information, with the following possible keys
		 *
		 * * appName: The App name
		 * * isDevel: Whether the App is in development mode or not
		 * * isUnderMaintenance: Whether the App is under maintenance or not
		 * * isCli: Whether the engine is running as cli or not. When not specified, it will autodetect.
		 * * configDir: The directory where configuration files are stored
		 * * appModulesDir: The directory where app modules are stored
		 * * appClassesDir: The directory where app classes are stored
		 * * timezoneName: The system's timezone. All modules, including Database for date/time retrievals/saves will be made taking this timezone into account. The server is expected to run on this timezone. Standard "Etc/UTC" is recommended.
		 * * timezoneId: The system's timezone. The same as timezoneName, but the matching id on the cherrycake timezones database table
		 * * baseCoreModules: An ordered array of the base Core module names that has to be always loaded on application start. Defaults to ["Actions"]. This list should include the Actions module to provide some kind of functionality to the app, since otherwise it wouldn't be answering any requests and will be completely unusable, except if you're experimenting with different ways of using the Cherrycake engine.
		 * * baseAppModules: An ordered array of the base App module names that has to be always loaded on application start.
		 * * additionalAppConfigFiles: An ordered array of any additional App config files to load that are found under the App config directory
		 *
		 * @return boolean Whether all the modules have been loaded ok
		 */
		function init($appNamespace, $setup = false) {
			$this->appNamespace = $appNamespace;

			if (!isset($setup["isCli"]))
				$setup["isCli"] = defined("STDIN");
			
			if (!isset($setup["appName"]))
				$setup["appName"] = md5(($_SERVER["HOSTNAME"] ?? false ?: "").$_SERVER["DOCUMENT_ROOT"]);
			
			foreach ([
				"appName",
				"isDevel",
				"isUnderMaintenance",
				"isCli",
				"configDir",
				"appModulesDir",
				"appClassesDir",
				"timezoneName",
				"timezoneId"
			] as $key)
				if (isset($setup[$key]))
					$this->$key = $setup[$key];
				
			if ($this->isDevel())
				$this->executionStartHrTime = hrtime(true);
			
			$this->engineCache = new EngineCache;
			
			$this->loadConstants();

			if ($this->isUnderMaintenance()) {
				header("HTTP/1.1 503 Service Temporarily Unavailable");
				echo file_get_contents("errors/maintenance.html");
				die;
			}
			
			date_default_timezone_set($this->getTimezoneName());

			require ENGINE_DIR."/Module.class.php";
			require ENGINE_DIR."/UiComponent.class.php";

			if (isset($setup["additionalAppConfigFiles"]))
				foreach ($setup["additionalAppConfigFiles"] as $additionalAppConfigFile)
					require APP_DIR."/config/".$additionalAppConfigFile;

			foreach ($setup["baseCoreModules"] ?? ["Actions"] as $module)
				if (!$this->loadCoreModule($module, MODULE_LOADING_ORIGIN_BASE))
					return false;
			
			if (isset($setup["baseAppModules"])) {
				foreach ($setup["baseAppModules"] as $module)
					if (!$this->loadAppModule($module, MODULE_LOADING_ORIGIN_BASE))
						return false;
			}

			return true;
		}

		/**
		 * @param string $directory The directory on which to search for modules
		 * @return mixed An array of the module names found on the specified directory, or false if none found or the directory couldn't be opened.
		 */
		function getAvailableModuleNamesOnDirectory($directory) {
			$cacheBucketName = "AvailableModuleNamesOnDirectory";
			$cacheKey = [$directory];
			$cacheTtl = $this->isDevel() ? 2 : 600;

			if ($this->engineCache->isKeyExistsInBucket($cacheBucketName, $cacheKey))
				return $this->engineCache->getFromBucket($cacheBucketName, $cacheKey);
			
			if (!is_dir($directory)) {
				$this->engineCache->setInBucket($cacheBucketName, $cacheKey, [], $cacheTtl);
				return false;
			}

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

			$this->engineCache->setInBucket($cacheBucketName, $cacheKey, $moduleNames ?? false, $cacheTtl);

			return $moduleNames;
		}

		/**
		 * @return array All the available Core module names
		 */
		function getAvailableCoreModuleNames() {
			return $this->getAvailableModuleNamesOnDirectory(ENGINE_DIR."/modules");
		}

		/**
		 * @return array All the available App module names
		 */
		function getAvailableAppModuleNames() {
			return $this->getAvailableModuleNamesOnDirectory($this->getAppModulesDir());
		}

		/**
		 * @param string $methodName the name of the method
		 * @return array The Core module names that implement the specified method
		 */
		function getAvailableCoreModuleNamesWithMethod($methodName) {
			return $this->getAvailableModuleNamesWithMethod("Cherrycake", ENGINE_DIR."/modules", $methodName);
		}

		/**
		 * @param string $methodName the name of the method
		 * @return array The App module names that implement the specified method
		 */
		function getAvailableAppModuleNamesWithMethod($methodName) {		
			return $this->getAvailableModuleNamesWithMethod($this->getAppNamespace(), $this->getAppModulesDir(), $methodName);
		}
		
		/*
		* @param string $nameSpace The namespace to use
		* @param string $modulesDirectory The directory where the specified module is stored
		* @param string $methodName the name of the method to check
		* @return array The module names that imeplement the specified method, o,r false if no modules found
		*/
		function getAvailableModuleNamesWithMethod($nameSpace, $modulesDirectory, $methodName) {
			$cacheBucketName = "AvailableModuleNamesWithMethod";
			$cacheKey = [$nameSpace, $modulesDirectory, $methodName];
			$cacheTtl = $this->isDevel() ? 2 : 600;
		
			if ($this->engineCache->isKeyExistsInBucket($cacheBucketName, $cacheKey))
				return $this->engineCache->getFromBucket($cacheBucketName, $cacheKey);
		
			if (!$moduleNames = $this->getAvailableModuleNamesOnDirectory($modulesDirectory)) {
				$this->engineCache->setInBucket($cacheBucketName, $cacheKey, [], $cacheTtl);
				return false;
			}

			foreach ($moduleNames as $moduleName) {
				if (!$this->isModuleExists($modulesDirectory, $moduleName))
					continue;
				if ($this->isModuleImplementsMethod($nameSpace, $modulesDirectory, $moduleName, $methodName))
					$modulesWithMethod[] = $moduleName;
			}

			$this->engineCache->setInBucket($cacheBucketName, $cacheKey, $modulesWithMethod ?? false, $cacheTtl);

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
			return $reflector->class == $className;
		}

		/**
		 * @return string The namespace used by the App
		 */
		function getAppNamespace() {
			return $this->appNamespace;
		}

		/**
		 * @return string The name of the App
		 */
		function getAppName() {
			return $this->appName;
		}

		/**
		 * @return bool Whether the App is in development mode or not
		 */
		function isDevel() {
			return $this->isDevel;
		}

		/**
		 * @return bool Whether the App is in "under maintenance" mode for the current client or not
		 */
		function isUnderMaintenance() {
			global $underMaintenanceExceptionIps;
			return $this->isUnderMaintenance && !in_array($_SERVER["REMOTE_ADDR"], $this->underMaintenanceExceptionIps);
		}

		/**
		 * @return bool Whether the app is running as cli or not
		 */
		function isCli() {
			return $this->isCli;
		}

		/**
		 * @return string The App directory where configuration files reside
		 */
		function getConfigDir() {
			return APP_DIR."/".$this->configDir;
		}

		/**
		 * @return string The App directory where app modules reside
		 */
		function getAppModulesDir() {
			return APP_DIR."/".$this->appModulesDir;
		}

		/**
		 * @return string The App directory where app classes reside
		 */
		function getAppClassesDir() {
			return APP_DIR."/".$this->appClassesDir;
		}

		/**
		 * @return string A string that identifies the system timezone
		 */
		function getTimezoneName() {
			return $this->timezoneName;
		}

		/**
		 * @return integer The system timezone id matching the one in the cherrycake timezones database table
		 */
		function getTimezoneId() {
			return $this->timezoneId;
		}

		/**
		 * Loads a Core module. Core modules are classes extending the module class that provide engine-specific functionalities.
		 *
		 * @param string $moduleName The name of the module to load
		 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
		 * @param string $requiredByModuleName The name of the module that required this module, if any.
		 *
		 * @return boolean Whether the module has been loaded ok
		 */
		function loadCoreModule($moduleName, $origin = MODULE_LOADING_ORIGIN_MANUAL, $requiredByModuleName = false) {
			return $this->loadModule(ENGINE_DIR."/modules", $this->getConfigDir(), $moduleName, __NAMESPACE__, $origin, $requiredByModuleName);
		}

		/**
		 * Loads an App module. App modules are classes extending the module class that provide app-specific functionalities.
		 *
		 * @param string $moduleName The name of the module to load
		 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
		 * @param string $requiredByModuleName The name of the module that required this module, if any.
		 *
		 * @return boolean Whether the module has been loaded ok
		 */
		function loadAppModule($moduleName, $origin = MODULE_LOADING_ORIGIN_MANUAL, $requiredByModuleName = false) {
			return $this->loadModule($this->getAppModulesDir(), $this->getConfigDir(), $moduleName, $this->getAppNamespace(), $origin, $requiredByModuleName);
		}

		/**
		 * Loads a module when it's not known whether it's an app or a core module
		 * 
		 * @param string $moduleName The name of the module to load
		 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
		 * @param string $requiredByModuleName The name of the module that required this module, if any.
		 * @return boolean Whether the module has been loaded ok
		 */
		function loadUnknownModule($moduleName, $origin = MODULE_LOADING_ORIGIN_MANUAL, $requiredByModuleName = false) {
			if ($this->isCoreModuleExists($moduleName))
				return $this->loadCoreModule($moduleName, $requiredByModuleName);
			return $this->loadAppModule($moduleName, $requiredByModuleName);
		}

		/**
		 * Generic method to load a module. Modules are classes extending the module class providing specific functionalities in a modular-type framework. Module can have its own configuration file.
		 *
		 * @param string $modulesDirectory Directory where modules are stored
		 * @param string $configDirectory Directory where module configuration files are stored with the syntax [module name].config.php
		 * @param string $moduleName The name of the module to load
		 * @param string $namespace The namespace of the module
		 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
		 * @param string $requiredByModuleName The name of the module that required this module, if any.
		 *
		 * @return boolean Whether the module has been loaded and initted ok
		 */
		function loadModule($modulesDirectory, $configDirectory, $moduleName, $namespace, $origin = MODULE_LOADING_ORIGIN_MANUAL, $requiredByModuleName = false) {
			if ($this->isDevel()) {
				$moduleLoadingHistoryId = uniqid();
				$this->moduleLoadingHistory[$moduleLoadingHistoryId] = [
					"loadingStartHrTime" => hrtime(true),
					"loadedModule" => $moduleName,
					"namespace" => $namespace,
					"origin" => $origin,
					"requiredBy" => $requiredByModuleName
				];
			}

			// Avoids a module to be loaded more than once
			if (is_array($this->loadedModules) && in_array($moduleName, $this->loadedModules)) {
				if ($this->isDevel())
					$this->moduleLoadingHistory[$moduleLoadingHistoryId]["isAlreadyLoaded"] = true;
				return true;
			}

			$this->loadedModules[] = $moduleName;

			$this->includeModuleClass($modulesDirectory, $moduleName);

			eval("\$this->".$moduleName." = new \\".$namespace."\\".$moduleName."();");

			if ($this->isDevel())
				$this->moduleLoadingHistory[$moduleLoadingHistoryId]["initStartHrTime"] = hrtime(true);

			if(!$this->$moduleName->init()) {
				if ($this->isDevel())
					$this->moduleLoadingHistory[$moduleLoadingHistoryId]["isInitFailed"] = true;
				$this->end();
				die;
			}

			if ($this->isDevel())
				$this->moduleLoadingHistory[$moduleLoadingHistoryId]["initEndHrTime"] = hrtime(true);

			return true;
		}

		/**
		 * @param string $moduleName The name of the module to check
		 * @return bool Whether the specified module has been loaded
		 */
		function isModuleLoaded($moduleName) {
			return isset($this->$moduleName);
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

		/**
		 * @param string $moduleName The name of the module
		 * @return boolean Whether the specified module exists and is a core module
		 */
		function isCoreModuleExists($moduleName) {
			return $this->isModuleExists(ENGINE_DIR."/modules", $moduleName);
		}

		/**
		 * @param string $moduleName The name of the module
		 * @return boolean Whether the specified module exists and is an app module
		 */
		function isAppModuleExists($moduleName) {
			return $this->isModuleExists($this->getAppModulesDir(), $moduleName);
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
		 * Loads a Cherrycake-specific class. Cherrycake classes are any other classes that are not modules, nor related to any Core module.
		 *
		 * @param $className The name of the class to load, must be stored in ENGINE_DIR/[class name].class.php
		 */
		function loadCherrycakeClass($className) {
			include_once(ENGINE_DIR."/".$className.".class.php");
		}

		/**
		 * Loads a cherrycake-specific class. Core module classes are any other classes that are not modules, but related to a Core module.
		 *
		 * @param $moduleName The name of the module to which the class belongs
		 * @param $className The name of the class
		 */
		function loadCoreModuleClass($moduleName, $className) {
			include_once(ENGINE_DIR."/modules/".$moduleName."/".$className.".class.php");
		}

		/**
		 * Loads an app-specific class. App classes are any other classes that are not directly related to a module.
		 *
		 * @param string $className The name of the class to load, must be stored in appClassesDir/[class name].class.php
		 */
		function loadAppClass($className) {
			include_once($this->getAppClassesDir()."/".$className.".class.php");
		}

		/**
		 * Loads an app-module specific class. App module classes are classes that do not extend the module class but provide functionalities related to a module.
		 *
		 * @param string $moduleName The name of the module to which the class belongs
		 * @param string $className The name of the class
		 */
		function loadAppModuleClass($moduleName, $className) {
			include_once($this->getAppModulesDir()."/".$moduleName."/".$className.".class.php");
		}

		// /**
		//  * Magic get method that tries to load modules if the the requested property is not found
		//  * @param string $key The key of the property or module name to get.
		//  * @return mixed The data. Null if data with the given key is not set.
		//  */
		// function __get($key) {
		// 	// if (property_exists($this, $key))
		// 	// 	return $this->$key;
		// 	if ($this->loadUnknownModule($key, MODULE_LOADING_ORIGIN_GETTER))
		// 		return $this->$key;
		// 	return false;
		// }

		/**
		 * Calls the specified static method on all the available Cherrycake and App modules where it's implemented, and then loads those modules
		 * @param string $methodName The method name to call
		 */
		function callMethodOnAllModules($methodName) {			
			// Call the static method
			$codeModuleNames = $this->getAvailableCoreModuleNamesWithMethod($methodName);
			if (is_array($codeModuleNames)) {
				foreach ($codeModuleNames as $coreModuleName) {
					$this->includeModuleClass(ENGINE_DIR."/modules", $coreModuleName);
					forward_static_call(["\\Cherrycake\\".$coreModuleName, $methodName]);
				}
				reset($codeModuleNames);
			}

			$appModuleNames = $this->getAvailableAppModuleNamesWithMethod($methodName);
			if (is_array($appModuleNames)) {
				foreach ($appModuleNames as $appModuleName) {
					$this->includeModuleClass($this->getAppModulesDir(), $appModuleName);
					forward_static_call(["\\".$this->getAppNamespace()."\\".$appModuleName, $methodName]);
				}
				reset($appModuleNames);
			}
			
			// Load the modules
			// if (is_array($coreModuleNames)) {
			// 	foreach ($coreModuleNames as $coreModuleName) {
			// 		$this->loadCoreModule($coreModuleName);
			// 	}
			// }

			// if (is_array($appModuleNames)) {
			// 	foreach ($appModuleNames as $appModuleName) {
			// 		$this->loadAppModule($appModuleName);
			// 	}
			// }
		}

		/**
		 * Loads all the available constants files
		 */
		function loadConstants() {
			$constantsFiles = $this->getAvailableConstantsFiles();
			if (sizeof($constantsFiles) < 1)
				return;
			foreach ($constantsFiles as $constantsFile)
				include_once($constantsFile);
		}

		/**
		 * @return array The file names of all the available constants files, or an empty array if none.
		 */
		function getAvailableConstantsFiles() {
			$cacheKey = "ConstantsFiles";
			$cacheTtl = $this->isDevel() ? 2 : 600;
			if ($this->engineCache->exists($cacheKey))
				return $this->engineCache->get($cacheKey);
			
			$constantsFiles = [];
			
			$coreModuleNames = $this->getAvailableCoreModuleNames();
			if (is_array($coreModuleNames)) {
				foreach ($coreModuleNames as $moduleName) {
					$constantsFileName = ENGINE_DIR."/modules/".$moduleName."/".$moduleName.".constants.php";
					if (file_exists($constantsFileName))
						$constantsFiles[] = $constantsFileName;
				}
			}

			$appModuleNames = $this->getAvailableAppModuleNames();
			if (is_array($appModuleNames)) {
				foreach ($appModuleNames as $moduleName) {
					$constantsFileName = $this->getAppModulesDir()."/".$moduleName."/".$moduleName.".constants.php";
					if (file_exists($constantsFileName))
						$constantsFiles[] = $constantsFileName;
				}
			}
			
			$this->engineCache->set($cacheKey, $constantsFiles, $cacheTtl);

			return $constantsFiles;
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

			if (!$this->isCli()) {
				header("HTTP/1.1 404");
				return false;
			}

			if ($argc < 2) {
				$this->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "No action name specified"
				]);
				die;
			}

			$actionName = $argv[1];
			if (!$action = $this->Actions->getAction($actionName)) {
				$this->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "Unknown action",
					"errorVariables" => [
						"actionName" => $actionName
					]
				]);
				die;
			}

			// If it has get parameters, parse them and put them in $_GET
			$_GET = $this->parseCommandLineArguments(array_slice($argv, 2));

			if (!$action->request->retrieveParameterValues())
				die;

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
		 * Returns information about the engine and its current status, including the loaded modules, the mapped actions and some benchmarks.
		 * Note that some information on the return array will be missing if the isDevel option has not been activated when initializing the engine.
		 * 
		 * @return array A hash array with the information
		 */
		function getStatus() {
			$r = [
				"appNamespace" => $this->getAppNamespace(),
				"appName" => $this->getAppName(),
				"isDevel" => $this->isDevel(),
				"isUnderMaintenance" => $this->isUnderMaintenance(),
				"documentRoot" => $_SERVER["DOCUMENT_ROOT"],
				"appModulesDir" => $this->getAppModulesDir(),
				"appClassesDir" => $this->getAppClassesDir(),
				"timezoneName" => $this->getTimezonename(),
				"timezoneId" => $this->getTimezoneId(),
				"executionStartHrTime" => $this->executionStartHrTime,
				"runningHrTime" =>
					$this->isDevel() ?
						hrtime(true) - $this->executionStartHrTime
					:
						null,
				"memoryUse" => memory_get_usage(),
				"memoryUsePeak" => memory_get_peak_usage(),
				"memoryAllocated" => memory_get_usage(true),
				"memoryAllocatedPeak" => memory_get_peak_usage(true),
				"hostname" => $_SERVER["HOSTNAME"] ?? false,
				"host" => $_SERVER["HTTP_HOST"] ?? false,
				"ip" => $_SERVER["REMOTE_ADDR"] ?? false,
				"os" => PHP_OS,
				"phpVersion" => phpversion(),			
				"serverSoftware" => $_SERVER["SERVER_SOFTWARE"],
				"serverGatewayInterface" => $_SERVER["GATEWAY_INTERFACE"],
				"serverApi" => PHP_SAPI
			];

			if (is_array($this->loadedModules))
				$r["loadedModules"] = $this->loadedModules;

			if (is_array($this->moduleLoadingHistory)) {
				$lastHrTime = null;
				$r["moduleLoadingHistory"] = $this->moduleLoadingHistory;
				reset($this->moduleLoadingHistory);
			}

			if ($this->isModuleLoaded("Actions"))
				$r["actions"] = $this->Actions->getStatus();
			
			if ($this->isModuleLoaded("Css"))
				$r["css"] = $this->Css->getStatus();

			if ($this->isModuleLoaded("Javascript"))
				$r["javascript"] = $this->Javascript->getStatus();

			return $r;
		}

		/**
		 * Returns a human-readable version of the status information provided by the getStatus method.
		 * @return array A hash array with the status information in a human readable format
		 */
		function getStatusHumanReadable() {
			$status = $this->getStatus();
			foreach ($status as $key => $value) {
				switch ($key) {
					case "runningHrTime":
						$r[$key] = number_format($value / 1000000, 4)."ms";
						break;
					case "moduleLoadingHistory":
						foreach ($value as $historyItem) {
							if ($historyItem["isAlreadyLoaded"] ?? false)
								continue;
							$r[$key][] =
								$historyItem["namespace"]."/".$historyItem["loadedModule"].
								" / ".
								[
									MODULE_LOADING_ORIGIN_MANUAL => "Manually loaded",
									MODULE_LOADING_ORIGIN_BASE => "Base module",
									MODULE_LOADING_ORIGIN_DEPENDENCY => "Required by ".$historyItem["requiredBy"],
									MODULE_LOADING_ORIGIN_AUTOLOAD => "Autoloaded",
									MODULE_LOADING_ORIGIN_GETTER => "Loaded in getter"
								][$historyItem["origin"]].
								" / loaded at ".number_format(($historyItem["loadingStartHrTime"] - $status["executionStartHrTime"]) / 1000000, 4)."ms".
								($historyItem["initEndHrTime"] ?? false ?
									" / init took ".number_format(($historyItem["initEndHrTime"] - $historyItem["initStartHrTime"]) / 1000000, 4)."ms"
								:
									" / didn't finish"
								);
						}
						break;
					case "actions":
						$r[$key] = $value["brief"] ?? false;
						break;
					default:
						$r[$key] = $value;
						break;
				}
			}
			return $r;
		}

		/**
		 * Returns an HTML version of the status in a human readable format.
		 * @return string The HTML code
		 */
		function getStatusHtml() {
			return prettyprint($this->getStatusHumanReadable(), true);
		}

		/**
		 * Ends the application by calling the end methods of all the loaded modules.
		 */
		function end() {
			if (is_array($this->loadedModules))
				foreach ($this->loadedModules as $moduleName)
					$this->$moduleName->end();
			die;
		}
	}

}

namespace {

	/**
	 * Autoloader for requested classes, to allow the automatic inclusion of class files when they're needed. It distinguishes from Cherrycake classes and App classes by checking the namespace
	 */
	spl_autoload_register(function ($className) {
		global $e;
		$namespace = strstr($className, "\\", true);

		// If autoload for Predis namespace is requested, don't do it. Exception for performance only.
		// This causes the "Predis" namespace name to be forbidden to use when creating a Cherrycake app.
		if ($namespace == "Predis")
			return false;

		$className = str_replace("\\", "/", substr(strrchr($className, "\\"), 1));
		$fileName = $className.".class.php";

		if ($namespace == "Cherrycake") {
			// Core class
			// First check if it exists as a class
			if (file_exists(ENGINE_DIR."/classes/".$fileName)) {
				include ENGINE_DIR."/classes/".$fileName;
			}
			// If not, check if it exists as a module
			else
			if ($e->isCoreModuleExists($className)) {
				// $e->loadCoreModule($className, \Cherrycake\MODULE_LOADING_ORIGIN_AUTOLOAD);
				$e->loadCoreModuleClass($className, $className); // We just load the module class instead of initting the module. This solves object inheritance dependencies on modules without causing complex dependency problems
			}
			// If not, throw an error
			else {
				$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "Core class or module \"$className\" could not be loaded automatically"
				]);
			}
		}
		else {
			// Non-core class or module
			if (file_exists($e->getAppClassesDir()."/".$fileName)) {
				include $e->getAppClassesDir()."/".$fileName;
			}
			// If not, check if it exists as a module
			else
			if ($e->isAppModuleExists($className)) {
				// $e->loadAppModule($className, \Cherrycake\MODULE_LOADING_ORIGIN_AUTOLOAD);
				$e->loadAppModuleClass($className, $className); // We just load the module class instead of initting the module. This solves object inheritance dependencies on modules without causing complex dependency problems
			}
			// If not, throw an error
			else {
				$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "App class or module \"$className\" could not be loaded automatically"
				]);
			}
		}
	});

	/**
	 * A helper function that pretty prints out a variable for debugging purposes
	 * @param $var The variable to debug
	 */

	function prettyprint($var, $isReturn = false, $isHtml = true) {
		$pretty =
			($isHtml ? "<pre>" : null).
			print_r($var, true). //json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS).
			($isHtml ? "<pre>" : null);
		
		if ($isReturn)
			return $pretty;
		else
			echo $pretty;
	}

}