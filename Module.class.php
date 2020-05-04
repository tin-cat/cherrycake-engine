<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * The base class for modules. Intented to be overloaded by specific functionality classes
 *
 * @package Cherrycake
 * @category Modules
 */
class Module extends BasicObject {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * @var bool $isConfigFileRequired Whether the config file for this module is required to run the app
	 */
	protected $isConfigFileRequired = false;

	/**
	 * @var array $config Holds the default configuration for this module
	 */
	protected $config;

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected $dependentCoreModules;

	/**
	 * @var array $dependentAppModules App module names that are required by this module
	 */
	protected $dependentAppModules;

	// /**
	//  * Returns a new instance of this module with the given optional configuration values.
	//  * @param array $properties Optional properties for the cloned object, just like in the config method
	//  * @return Module The module
	//  */
	// function clone($properties = false) {
	// 	$cloned = clone $this;
	// 	$cloned->setProperties($properties);
	// 	return $cloned;
	// }

	/**
	 * @return string This module's name
	 */
	function getName() {
		return substr(get_class($this), strrpos(get_class($this), "\\")+1);
	}

	/**
	 * loadConfigFile
	 *
	 * Loads the configuration file for this module, if there's one
	 */
	function loadConfigFile() {
		if ($this->isConfigFile) {
			global $e;
			$className = $this->getName();
			$fileName = $e->getConfigDir()."/".$className.".config.php";
			if (!file_exists($fileName)) {
				if ($this->isConfigFileRequired)
					trigger_error("Configuration file $fileName required");
				return;
			}
			include $fileName;
			$this->config(${$className."Config"});
		}
	}

	/**
	 * config
	 *
	 * Sets the module configuration
	 *
	 * @param array $config An array of configuration options for this module. It merges them with the hard coded default values configured in the overloaded module.
	 */
	function config($config) {
		if (!$config)
			return;

		if (is_array($this->config))
			$this->config = $this->arrayMergeRecursiveDistinct($this->config, $config);
		else
			$this->config = $config;
	}

	/**
	 * @param string $key The configuration key
	 * @return bool Whether the configuration specified the given configuration key has been set or not
	 */
	function isConfig($key) {
		return isset($this->config[$key]);
	}

	/**
	 * getConfig
	 *
	 * Gets a configuration value
	 *
	 * @param string $key The configuration key
	 * @return mixed The value of the specified config key. Returns false if doesn't exists.
	 */
	function getConfig($key) {
		if (isset($this->config[$key]))
			return $this->config[$key];
		else
			return false;
	}

	/**
	 * setConfig
	 *
	 * Sets a configuration value
	 *
	 * @param string $key The configuration key, or a hash array of keys => values if multiple keys are to be changed
	 * @param string $value The configuration value
	 */
	function setConfig($keyOrKeys, $value = false) {
		if (is_array($keyOrKeys)) {
			foreach ($keyOrKeys as $key => $value)
				$this->config[$key] = $value;
		}
		else
			$this->config[$keyOrKeys] = $value;
	}

	/**
	 * loadDependencies
	 *
	 * Loads the dependent modules required by this one
	 *
	 * @return boolean Whether the dependent modules were loaded ok
	 */
	function loadDependencies() {
		global $e;

		if (is_array($this->dependentCoreModules)) {
			foreach ($this->dependentCoreModules as $moduleName) {
				if (!$e->loadCoreModule($moduleName, MODULE_LOADING_ORIGIN_DEPENDENCY, $this->getName()))
					return false;
			}
		}

		if (is_array($this->dependentAppModules)) {
			foreach ($this->dependentAppModules as $moduleName) {
				if (!$e->loadAppModule($moduleName, MODULE_LOADING_ORIGIN_DEPENDENCY, $this->getName()))
					return false;
			}
		}

		return true;
	}

	/**
	 * mapActions
	 *
	 * Maps the Actions to which this module must respond. Should be overloaded by a module class when needed. Intended to contain calls to Actions::mapAction()
	 */
	public static function mapActions() {
	}

	/**
	 * mapTableAdmin
	 * 
	 * Maps the TableAdmins which this module must respond. Should be overloaded by a module class when needed. Intended to contain calls to TableAdmin::map()
	 */
	public static function mapTableAdmin() {
	}

	/**
	 * mapItemAdmin
	 * 
	 * Maps the ItemAdmins which this module must respond. Should be overloaded by a module class when needed. Intended to contain calls to ItemAdmin::map()
	 */
	public static function mapItemAdmin() {
	}

	/**
	 * addCssAndJavascript
	 *
	 * Adds the Css/Javascript files/code needed by this module to the proper set on Css and Javascript modules.
	 */
	public function addCssAndJavascript() {
	}

	/**
	 * init
	 *
	 * Initializes the module, intended to be overloaded.
	 * Called when the module is loaded.
	 * Contains any specific initializations for the module, and any required loading of modules and classes dependencies.
	 *
	 * @return boolean Whether the module has been loaded ok
	 */
	function init() {
		if (!$this->loadDependencies())
			return false;
		$this->loadConfigFile();
		return true;
	}

	/**
	 * Performs any tasks needed to end this module.
	 * Called when the engine ends.
	 */
	function end() {
	}

	/**
	 * arrayMergeRecursiveDistinct
	 *
	 * Joins two arrays like PHP function array_merge_recursive_distinct does, but instead it does not adds elements to arrays when keys match: it replaces them.
	 *
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 *
	 * @param array $array1 The first array to merge
	 * @param array $array2 The second array to merge
	 * @return array The merged array
	 */
	function arrayMergeRecursiveDistinct(array &$array1, array &$array2) {
		$merged = $array1;

		foreach ($array2 as $key => &$value)
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
				$merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
			else
				$merged[$key] = $value;

		return $merged;
	}
}