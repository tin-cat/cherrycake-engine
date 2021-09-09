<?php

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
	 * @var EngineCache $engineCache Holds the bottom-level Cache object
	 */
	public EngineCache $engineCache;

	/**
	 * @var array $loadedModules Stores the names of all included modules
	 */
	private array $loadedModules = [];

	/**
	 * @var array $moduleLoadingHistory Stores a history of the loaded modules.
	 */
	private array $moduleLoadingHistory = [];

	/**
	 * @var int $executionStartHrTime The system's high resolution time where the execution started
	 */
	private int $executionStartHrTime;

	/**
	 * Constructs an engine
	 * @param string $appNamespace The namespace of the app
	 * @param string $appName The App name
	 * @param bool $isDevel Whether the App is in development mode or not
	 * @param bool $isUnderMaintenance Whether the App is under maintenance or not
	 * @param string $configDir The directory where configuration files are stored
	 * @param string $appModulesDir The directory where app modules are stored
	 * @param string $appClassesDir The directory where app classes are stored
	 * @param string $timezoneName The system's timezone. All modules, including Database for date/time retrievals/saves will be made taking this timezone into account. The server is expected to run on this timezone. Standard "Etc/UTC" is recommended.
	 * @param int $timezoneId The system's timezone. The same as timezoneName, but the matching id on the cherrycake timezones database table
	 * @param bool $isCli Whether the engine is running as cli or not. When not specified, it will autodetect
	 * @param array $underMaintenanceExceptionIps An array of IPs that will override the $isDevel parameter to false
	 */
	public function __construct(
		private string $appNamespace = 'App',
		private string $appName = '',
		private bool $isDevel = false,
		private bool $isUnderMaintenance = false,
		private string $configDir = 'config',
		private string $appModulesDir = 'src',
		private string $appClassesDir = 'src',
		private string $timezoneName = 'Etc/UTC',
		private int $timezoneId = 532,
		private bool|null $isCli = null,
		private array $underMaintenanceExceptionIps = [],
	) {
		if ($this->appName === '')
			$this->appName = md5(($_SERVER["HOSTNAME"] ?? false ?: '').$_SERVER["DOCUMENT_ROOT"]);

		if (is_null($this->isCli))
			$this->isCli = defined('STDIN');
	}

	/**
	 * Initializes the engine
	 * @param array $baseCoreModules An ordered array of the base Core module names that has to be always loaded on application start. Defaults to ["Actions"]. This list should include the Actions module to provide some kind of functionality to the app, since otherwise it wouldn't be answering any requests and will be completely unusable, except if you're experimenting with different ways of using the Cherrycake engine
	 * @param array $baseAppModules An ordered array of the base App module names that has to be always loaded on application start
	 * @param array $additionalAppConfigFiles An ordered array of any additional App config files to load that are found under the App config directory
	 * @return boolean Whether all the modules have been loaded ok
	 */
	public function init(
		array $baseCoreModules = ['Actions'],
		array $baseAppModules = [],
		array $additionalAppConfigFiles = []
	): bool {

		if ($this->isDevel())
			$this->executionStartHrTime = hrtime(true);

		$this->engineCache = new EngineCache;

		if ($this->isUnderMaintenance()) {
			header("HTTP/1.1 503 Service Temporarily Unavailable");
			echo file_get_contents("errors/maintenance.html");
			die;
		}

		date_default_timezone_set($this->getTimezoneName());

		if (count($additionalAppConfigFiles)) {
			foreach ($additionalAppConfigFiles as $fileName)
				require APP_DIR."/config/".$fileName;
		}

		foreach ($baseCoreModules as $module) {
			if (!$this->loadCoreModule($module, MODULE_LOADING_ORIGIN_BASE))
				return false;
		}

		if (count($baseAppModules)) {
			foreach ($baseAppModules as $module) {
				if (!$this->loadAppModule($module, MODULE_LOADING_ORIGIN_BASE))
					return false;
			}
		}

		return true;
	}

	/**
	 * @param string $directory The directory on which to search for modules
	 * @return array An array of the module names found on the specified directory
	 */
	private function getAvailableModuleNamesOnDirectory(string $directory): array {
		$cacheBucketName = "AvailableModuleNamesOnDirectory";
		$cacheKey = [$directory];
		$cacheTtl = $this->isDevel() ? 1 : 60;

		if ($this->engineCache->isKeyExistsInBucket($cacheBucketName, $cacheKey))
			return $this->engineCache->getFromBucket($cacheBucketName, $cacheKey);

		if (!is_dir($directory)) {
			$this->engineCache->setInBucket($cacheBucketName, $cacheKey, [], $cacheTtl);
			return false;
		}

		$moduleNames = [];
		if (!$handler = opendir($directory))
			return [];
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
	 * @return array All the available App module names
	 */
	private function getAvailableAppModuleNames(): array {
		return $this->getAvailableModuleNamesOnDirectory($this->getAppModulesDir());
	}

	/**
	 * @param string $methodName the name of the method
	 * @return array The Core module names that implement the specified method
	 */
	private function getAvailableCoreModuleNamesWithMethod(string $methodName): array {
		return $this->getAvailableModuleNamesWithMethod("Cherrycake", ENGINE_DIR."/src", $methodName);
	}

	/**
	 * @param string $methodName the name of the method
	 * @return array The App module names that implement the specified method
	 */
	private function getAvailableAppModuleNamesWithMethod(string $methodName): array {
		return $this->getAvailableModuleNamesWithMethod($this->getAppNamespace(), $this->getAppModulesDir(), $methodName);
	}

	/*
	* @param string $nameSpace The namespace to use
	* @param string $modulesDirectory The directory where the specified module is stored
	* @param string $methodName the name of the method to check
	* @return array The module names that imeplement the specified method, o,r false if no modules found
	*/
	private function getAvailableModuleNamesWithMethod(string $nameSpace, string $modulesDirectory, string $methodName): array {
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
			if ($this->isModuleImplementsMethod($nameSpace, $moduleName, $methodName))
				$modulesWithMethod[] = $moduleName;
		}

		$this->engineCache->setInBucket($cacheBucketName, $cacheKey, $modulesWithMethod ?? false, $cacheTtl);

		return $modulesWithMethod ?? false;
	}

	/**
	 * @param string $nameSpace The namespace to use
	 * @param string $moduleName The name of the module to check
	 * @param string $methodName the name of the method to check
	 * @return boolean True if the specified module implements the specified method
	 */
	private function isModuleImplementsMethod(string $nameSpace, string $moduleName, string $methodName): bool {
		return $this->isClassMethodImplemented($nameSpace."\\".$moduleName."\\".$moduleName, $methodName);
	}

	/**
	 * @param string $className The name of the class
	 * @param string $methodname The name of the method
	 * @return boolean True if the method is implemented on the specified class, false if it isn't.
	 */
	private function isClassMethodImplemented(string $className, string $methodName): bool {
		$reflector = new \ReflectionMethod($className, $methodName);
		return $reflector->class == $className;
	}

	/**
	 * @return string The namespace used by the App
	 */
	public function getAppNamespace(): string {
		return $this->appNamespace;
	}

	/**
	 * @return string The name of the App
	 */
	public function getAppName(): string {
		return $this->appName;
	}

	/**
	 * @return bool Whether the App is in development mode or not
	 */
	public function isDevel(): bool {
		return $this->isDevel;
	}

	/**
	 * @return bool Whether the App is in "under maintenance" mode for the current client or not
	 */
	public function isUnderMaintenance(): bool {
		return $this->isUnderMaintenance && !in_array($_SERVER["REMOTE_ADDR"], $this->underMaintenanceExceptionIps);
	}

	/**
	 * @return bool Whether the app is running as cli or not
	 */
	public function isCli(): bool {
		return $this->isCli;
	}

	/**
	 * @return string The App directory where configuration files reside
	 */
	public function getConfigDir(): string {
		return APP_DIR."/".$this->configDir;
	}

	/**
	 * @return string The App directory where app modules reside
	 */
	public function getAppModulesDir(): string {
		return APP_DIR."/".$this->appModulesDir;
	}

	/**
	 * @return string The App directory where app classes reside
	 */
	public function getAppClassesDir(): string {
		return APP_DIR."/".$this->appClassesDir;
	}

	/**
	 * @return string A string that identifies the system timezone
	 */
	public function getTimezoneName(): string {
		return $this->timezoneName;
	}

	/**
	 * @return int The system timezone id matching the one in the cherrycake timezones database table
	 */
	public function getTimezoneId(): int {
		return $this->timezoneId;
	}

	/**
	 * Loads a Core module. Core modules are classes extending the module class that provide engine-specific functionalities.
	 * @param string $moduleName The name of the module to load
	 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
	 * @param string $requiredByModuleName The name of the module that required this module, if any.
	 * @return boolean Whether the module has been loaded ok
	 */
	public function loadCoreModule(
		string $moduleName,
		int $origin = MODULE_LOADING_ORIGIN_MANUAL,
		bool $requiredByModuleName = false
	): bool {
		return $this->loadModule(ENGINE_DIR."/src", $this->getConfigDir(), $moduleName, __NAMESPACE__, $origin, $requiredByModuleName);
	}

	/**
	 * Loads an App module. App modules are classes extending the module class that provide app-specific functionalities.
	 * @param string $moduleName The name of the module to load
	 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
	 * @param string $requiredByModuleName The name of the module that required this module, if any.
	 * @return boolean Whether the module has been loaded ok
	 */
	public function loadAppModule(
		string $moduleName,
		int $origin = MODULE_LOADING_ORIGIN_MANUAL,
		bool $requiredByModuleName = false
	): bool {
		return $this->loadModule($this->getAppModulesDir(), $this->getConfigDir(), $moduleName, $this->getAppNamespace(), $origin, $requiredByModuleName);
	}

	/**
	 * Loads a module when it's not known whether it's an app or a core module
	 * @param string $moduleName The name of the module to load
	 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
	 * @param string $requiredByModuleName The name of the module that required this module, if any.
	 * @return boolean Whether the module has been loaded ok
	 */
	public function loadUnknownModule(
		string $moduleName,
		int $origin = MODULE_LOADING_ORIGIN_MANUAL,
		bool $requiredByModuleName = false
	): bool {
		if ($this->isCoreModuleExists($moduleName))
			return $this->loadCoreModule($moduleName, $origin, $requiredByModuleName);
		return $this->loadAppModule($moduleName, $origin, $requiredByModuleName);
	}

	/**
	 * Generic method to load a module. Modules are classes extending the module class providing specific functionalities in a modular-type framework. Module can have its own configuration file.
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $configDirectory Directory where module configuration files are stored with the syntax [module name].config.php
	 * @param string $moduleName The name of the module to load
	 * @param string $namespace The namespace of the module
	 * @param int $origin The origin from where the module is being loaded, one of the MODULE_LOADING_ORIGIN_? constants, defaults to MODULE_LOADING_ORIGIN_MANUAL
	 * @param string $requiredByModuleName The name of the module that required this module, if any.
	 * @return boolean Whether the module has been loaded and initted ok
	 */
	private function loadModule(
		string $modulesDirectory,
		string $configDirectory,
		string $moduleName,
		string $namespace,
		int $origin = MODULE_LOADING_ORIGIN_MANUAL,
		bool $requiredByModuleName = false
	): bool {
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
		if (in_array($moduleName, $this->loadedModules)) {
			if ($this->isDevel())
				$this->moduleLoadingHistory[$moduleLoadingHistoryId]["isAlreadyLoaded"] = true;
			return true;
		}

		$this->loadedModules[] = $moduleName;

		eval("\$this->".$moduleName." = new \\".$namespace."\\".$moduleName."\\".$moduleName.";");

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
	public function isModuleLoaded(string $moduleName): bool {
		return isset($this->$moduleName);
	}

	/**
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $moduleName The name of the module whose class must be included
	 * @return string The file path of the specified module
	 */
	private function getModuleFilePath(string $modulesDirectory, string $moduleName): string {
		return $modulesDirectory."/".$moduleName."/".$moduleName.".php";
	}

	/**
	 * @param string $modulesDirectory Directory where modules are stored
	 * @param string $moduleName The name of the module whose class must be included
	 * @return boolean Whether the specified module file exists
	 */
	private function isModuleExists(string $modulesDirectory, string $moduleName): bool {
		return file_exists($this->getModuleFilePath($modulesDirectory, $moduleName));
	}

	/**
	 * @param string $moduleName The name of the module
	 * @return boolean Whether the specified module exists and is a core module
	 */
	private function isCoreModuleExists(string $moduleName): bool {
		return $this->isModuleExists(ENGINE_DIR."/src", $moduleName);
	}

	/**
	 * @param string $moduleName The name of the module
	 * @return boolean Whether the specified module exists and is an app module
	 */
	private function isAppModuleExists(string $moduleName): bool {
		return $this->isModuleExists($this->getAppModulesDir(), $moduleName);
	}

	/**
	 * Calls the specified static method on all the available Cherrycake and App modules where it's implemented, and then loads those modules
	 * @param string $methodName The method name to call
	 */
	public function callMethodOnAllModules(string $methodName) {
		// Call the static method
		$coreModuleNames = $this->getAvailableCoreModuleNamesWithMethod($methodName);
		if (is_array($coreModuleNames)) {
			foreach ($coreModuleNames as $coreModuleName) {
				forward_static_call(["\\Cherrycake\\".$coreModuleName."\\".$coreModuleName, $methodName]);
			}
			reset($coreModuleNames);
		}

		$appModuleNames = $this->getAvailableAppModuleNamesWithMethod($methodName);
		if (is_array($appModuleNames)) {
			foreach ($appModuleNames as $appModuleName) {

				forward_static_call(["\\".$this->getAppNamespace()."\\".$appModuleName."\\".$appModuleName, $methodName]);
			}
			reset($appModuleNames);
		}
	}

	/**
	 * Magic get method to get a module.
	 * It loads the module if it hasn't been loaded.
	 * @param string $key The name of the module
	 * @return mixed The module, the local property value if it exists, or false otherwise.
	 */
	function __get($key) {
		// First check if the module is already loaded, or if it is a local property
		if (isset($this->$key))
			return $this->$key;

		// Try to load the module
		if ($this->loadUnknownModule($key))
			return $this->$key;

		return false;
	}

	/**
	 * Attends the request received from a web server by calling Actions::run with the requested URI string
	 */
	public function attendWebRequest() {
		$this->Actions->run($_SERVER["REQUEST_URI"]);
	}

	/**
	 * Attends the request received by the PHP cli by calling Actions:run with the first command line argument, which should be a URI
	 */
	public function attendCliRequest() {
		global $argv, $argc;

		if (!$this->isCli()) {
			header("HTTP/1.1 404");
			return false;
		}

		if ($argc < 2) {
			$this->Errors->trigger(ERROR_SYSTEM, [
				"errorDescription" => "No action name specified"
			]);
			die;
		}

		$actionName = $argv[1];
		if (!$action = $this->Actions->getAction($actionName)) {
			$this->Errors->trigger(ERROR_SYSTEM, [
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
	private function parseCommandLineArguments(array $params, array $noopt = []): array {
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
	 * @return array A hash array with the information
	 */
	public function getStatus(): array {
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
	public function getStatusHumanReadable(): array {
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
	public function getStatusHtml(): string {
		return prettyprint($this->getStatusHumanReadable(), true);
	}

	/**
	 * Ends the application by calling the end methods of all the loaded modules.
	 */
	public function end() {
		if (is_array($this->loadedModules))
			foreach ($this->loadedModules as $moduleName)
				$this->$moduleName->end();
		die;
	}

	/**
	 * Helper to easily get a translatable string using Language\Translation
	 * @param string $baseLanguageText The translated text in the base language.
	 * @param string $category An optional text category name, to better organize translation files.
	 * @param int $baseLanguage The language on which the provided $baseLanguageText is. If not specified, the `defaultBaseLanguage` Translation configuration is assumed.
	 * @return Translation\Text A Text object for the given key
	 */
	public function t(...$parameters)
	: Translation\Text {
		return new Translation\Text(...$parameters);
	}
}

/**
 * A helper function that pretty prints out a variable for debugging purposes
 * @param $var The variable to debug
 * @return string The prettyfied representation
 */

function prettyprint($var, $isReturn = false, $isHtml = true): string {
	$pretty =
		($isHtml ? "<pre>" : null).
		print_r($var, true). // json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS).
		($isHtml ? "<pre>" : null);

	if ($isReturn)
		return $pretty;
	else
		echo $pretty;
}
