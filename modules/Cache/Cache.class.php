<?php

/**
 * Cache
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Cache
 *
 * Manages cache providers.
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 *
 * @package Cherrycake
 * @category Modules
 */
class Cache  extends \Cherrycake\Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var bool $isConfigFileRequired Whether the config file for this module is required to run the app
	 */
	protected $isConfigFileRequired = false;

	/**
	 * init
	 *
	 * Initializes the module and loads the base CacheProvider class
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		if (!parent::init())
			return false;
		
		global $e;
		
		// Check that the "engine" cache provider has not been defined previously
		if ($e->isDevel() && isset($this->getConfig("providers")["engine"])) {
			$e->loadCoreModule("Errors");
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				"errorDescription" => "The \"engine\" cache provider name is reserved"
			]);
		}
		
		// Setup the engine cache
		$this->config["providers"]["engine"] = [
			"providerClassName" => "CacheProviderApcu"
		];

		global $e;
		$e->loadCoreModuleClass("Cache", "CacheProvider");

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers")))
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], (isset($provider["config"]) ? $provider["config"] : null));

		return true;
	}

	/**
	 * addProvider
	 *
	 * Adds a cache provider
	 *
	 * @param string $key The key to later access the cache provider
	 * @param string $providerClassName The cache provider class name
	 * @param array $config The configuration for the cache provider
	 */
	function addProvider($key, $providerClassName, $config) {
		global $e;
		$e->loadCoreModuleClass("Cache", $providerClassName);
		eval("\$this->".$key." = new \\Cherrycake\\".$providerClassName."();");
		$this->$key->config($config);
	}

	/**
	 * buildCacheKey
	 *
	 * Returns a cache key to be used in caching operations, based on the provided $config.
	 * The keys built can have one of the following syntaxes:
	 * <App namespace>_[<prefix>]_<uniqueId>
	 * <App namespace>_[<prefix>]_[<specificPrefix>]_<key|encoded sql>
	 *
	 * @param $cacheKeyNamingOptions The config options to build the cache key, holds the following key-value options:
	 * "prefix": A prefix to use
	 * "uniqueId": A unique id for the cache key that will override any other specific key identifier config options
	 * "specificPrefix": A secondary prefix to prepend to provided sql or key config values
	 * "hash": A string to be hashed as the cache key instead of using "key". For example: A SQL query
	 * "key": An arbitrary key to uniquely identify the cache key
	 *
	 * @return string The final cache key
	 */
	static function buildCacheKey($cacheKeyNamingOptions) {
		global $e;
		$key = $e->getAppName();

		if (isset($cacheKeyNamingOptions["prefix"]))
			$key .= "_".$cacheKeyNamingOptions["prefix"];

		if (isset($cacheKeyNamingOptions["uniqueId"]))
			return $key."_".$cacheKeyNamingOptions["uniqueId"];

		if (isset($cacheKeyNamingOptions["specificPrefix"]))
			$key .= "_".$cacheKeyNamingOptions["specificPrefix"];

		if (isset($cacheKeyNamingOptions["hash"]))
			return  $key."_".hash("md5", $cacheKeyNamingOptions["hash"]);

		return $key."_".$cacheKeyNamingOptions["key"];
	}
}