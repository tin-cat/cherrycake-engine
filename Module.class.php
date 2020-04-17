<?php

/**
 * Module
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Module
 *
 * The base class for modules. Intented to be overloaded by specific functionality classes
 *
 * @package Cherrycake
 * @category Modules
 */
class Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * @var array $config Holds the default configuration for this module
	 */
	protected $config;

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	protected $dependentCherrycakeModules;

	/**
	 * @var array $dependentAppModules App module names that are required by this module
	 */
	protected $dependentAppModules;

	/**
	 * loadConfigFile
	 *
	 * Loads the configuration file for this module, if there's one
	 */
	function loadConfigFile() {
		if ($this->isConfigFile) {
			global $e;
			$className = substr(get_class($this), strrpos(get_class($this), "\\")+1);
			$fileName = $e->getConfigDir()."/".$className.".config.php";
			if (!file_exists($fileName))
				return;
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

		if (is_array($this->dependentCherrycakeModules))
			foreach ($this->dependentCherrycakeModules as $moduleName)
				if (!$e->loadCherrycakeModule($moduleName))
					return false;

		if (is_array($this->dependentAppModules))
			foreach ($this->dependentAppModules as $moduleName)
				if (!$e->loadAppModule($moduleName))
					return false;

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