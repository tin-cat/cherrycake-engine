<?php

/**
 * UiComponent
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponent
 *
 * Base class for Ui components.
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponent extends BasicObject {
	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents;

	/**
	 * @var array $dependentAppUiComponents App UiComponent names that are required by this module
	 */
	protected $dependentAppUiComponents;

	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * @var array $config Holds the default configuration for this UiComponent
	 */
	protected $config;

	/**
	 * @param array $properties A hash array with the setup keys
	 */
	function __construct($properties = false) {
		parent::__construct($properties);
		$this->init();
	}

	/**
	 * loadConfigFile
	 *
	 * Loads the configuration file for this UiComponent, if there's one
	 */
	function loadConfigFile() {
		if ($this->isConfigFile) {
			global $e;
			$className = substr(get_class($this), strpos(get_class($this), "\\")+1);
			$fileName = $e->getConfigDir()."/UiComponents/".$className.".config.php";
			if (!file_exists($fileName))
				return;
			include $fileName;
			$this->config(${$className."Config"});
		}
	}

	/**
	 * config
	 *
	 * Sets the ui component configuration
	 *
	 * @param array $config An array of configuration options for this ui component. It merges them with the hard coded default values configured in the overloaded ui component class.
	 */
	function config($config) {
		if (!$config)
			return;
		if (is_array($this->config))
			$this->config = array_merge($this->config, $config);
		else
			$this->config = $config;
	}

	/**
	 * init
	 *
	 * Initializes the UiComponent, intended to be overloaded.
	 * Called when the UiComponent is loaded.
	 * Contains any specific initializations for the UiComponent, and any required loading of dependencies.
	 *
	 * @return boolean Whether the UiComponent has been loaded ok
	 */
	function init() {
		if (!$this->loadDependencies())
			return false;

		$this->loadConfigFile();

		// Default configuration
		if (!isset($this->config["cssSetName"]))
			$this->config["cssSetName"] = "cherrycakeUiComponents";

		if (!isset($this->config["javascriptSetName"]))
			$this->config["javascriptSetName"] = "cherrycakeUiComponents";

		$this->AddCssAndJavascriptSetsToHtmlDocument();

		return true;
	}

	/**
	 * loadDependencies
	 *
	 * Loads the dependent UiComponents required by this one
	 *
	 * @return boolean Whether the dependent UiComponents are loaded ok
	 */
	function loadDependencies() {
		global $e;

		if (is_array($this->dependentCherrycakeUiComponents)) {
			foreach ($this->dependentCherrycakeUiComponents as $UiComponentName)
				$e->Ui->addCherrycakeUiComponent($UiComponentName);
			reset($this->dependentCherrycakeUiComponents);
		}

		if (is_array($this->dependentAppUiComponents)) {
			foreach ($this->dependentAppUiComponents as $UiComponentName)
				$e->Ui->addAppUiComponent($UiComponentName);
			reset($this->dependentAppUiComponents);
		}

		return true;
	}

	/**
	 * getConfig
	 *
	 * Gets a configuration value
	 *
	 * @param mixed $key The configuration key, null if it doesn't exists
	 */
	function getConfig($key) {
		return $this->config[$key] ?? null;
	}

	/**
	 * @return boolean Whether the specified config key has been set or not
	 */
	function isConfig($key) {
		return isset($this->config[$key]);
	}

	/**
	 * If the property with the given key has been set, returns its value. If not, returns the config value with the same key. Returns false if neither is set. Normally used to treat parameters for UiComponents that have a default value specified on its config file, but still can be overriden by passing it as a parameter when calling the UiComponent build method.
	 * 
	 * @param mixed The value of the property or the config key, or false if neither set.
	 */
	function getPropertyOrConfig($key) {
		if (isset($this->$key))
			return $this->$key;
		if ($this->isConfig($key))
			return $this->getConfig($key);
		return false;
	}

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function AddCssAndJavascriptSetsToHtmlDocument() {
		global $e;
		$e->HtmlDocument->addCssSet("cherrycakemain");
		$e->HtmlDocument->addJavascriptSet("cherrycakemain");

		if ($this->getConfig("cssSetName"))
			$e->HtmlDocument->addCssSet($this->getConfig("cssSetName"));

		if ($this->getConfig("javascriptSetName"))
			$e->HtmlDocument->addJavascriptSet($this->getConfig("javascriptSetName"));
	}

	/**
	 * addCssAndJavascript
	 *
	 * Adds the required Css/Javascript files/code for this Ui component. Intended to be overloaded by an specific UiComponent class, which has to call first parent::addCssAndJavascript in order to retain UiComponent modules dependencies, and load Javascript/Css in the intended order
	 * Called when the Css is dumped
	 */
	function addCssAndJavascript() {
		global $e;

		if (is_array($this->dependentCherrycakeUiComponents)) {
			foreach ($this->dependentCherrycakeUiComponents as $UiComponentName)
				$e->Ui->uiComponents[$UiComponentName]->addCssAndJavascript();
			reset($this->dependentCherrycakeUiComponents);
		}

		if (is_array($this->dependentAppUiComponents)) {
			foreach ($this->dependentAppUiComponents as $UiComponentName)
				$e->Ui->uiComponents[$UiComponentName]->addCssAndJavascript();
			reset($this->dependentAppUiComponents);
		}
	}

	function __toString() {
		return $this->buildHtml();
	}
}